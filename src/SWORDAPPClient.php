<?php

namespace Swordapp\Client;

class SWORDAPPClient
{
    const SAL_USER_AGENT = "User-Agent: SWORDAPP PHP v2 library (version 0.1) http://php.swordapp.org/";

    /**
     * Curl debug mode
     *
     * @var bool
     */
    private $debug = false;

    /**
     * Curl options
     *
     * @var array
     */
    private $curl_opts = array();

    /**
     * @param array $curl_opts
     */
    public function __construct($curl_opts = array())
    {
        $this->curl_opts = $curl_opts;
    }

    /**
     * Request a Service Document from the specified url, with the specified credentials,
     * and on-behalf-of the specified user.
     *
     * @param  string $sac_url
     * @param  string $sac_u
     * @param  string $sac_p
     * @param  string $sac_obo
     * @return SWORDAPPServiceDocument
     * @throws \Exception
     */
    public function servicedocument($sac_url, $sac_u, $sac_p, $sac_obo)
    {
        // Get the service document
        $sac_curl = $this->curlInit($sac_url, $sac_u, $sac_p);

        $headers = array();
        array_push($headers, self::SAL_USER_AGENT);
        if (!empty($sac_obo)) {
            array_push($headers, "X-On-Behalf-Of: " . $sac_obo);
        }
        curl_setopt($sac_curl, CURLOPT_HTTPHEADER, $headers);
        $sac_resp = curl_exec($sac_curl);
        $sac_status = curl_getinfo($sac_curl, CURLINFO_HTTP_CODE);
        curl_close($sac_curl);

        // Parse the result
        if ($sac_status == 200) {
            try {
                $sac_sdresponse = new SWORDAPPServiceDocument($sac_url, $sac_status, $sac_resp);
            } catch (\Exception $e) {
                throw new \Exception("Error parsing service document (" . $e->getMessage() . ")");
            }
        } else {
            $sac_sdresponse = new SWORDAPPServiceDocument($sac_url, $sac_status);
        }

            // Return the Service Document object
            return $sac_sdresponse;
    }

    /**
     * Perform a deposit to the specified url, with the specified credentials,
     * on-behalf-of the specified user, and with the given file and formatnamespace and noop setting
     *
     * @param  string $sac_url
     * @param  string $sac_u
     * @param  string $sac_p
     * @param  string $sac_obo
     * @param  string $sac_fname
     * @param  string $sac_packaging (optional)
     * @param  string $sac_contenttype (optional)
     * @param  bool $sac_inprogress (optional)
     * @return SWORDAPPEntry|SWORDAPPErrorDocument
     * @throws \Exception
     */
    public function deposit(
        $sac_url,
        $sac_u,
        $sac_p,
        $sac_obo,
        $sac_fname,
        $sac_packaging = '',
        $sac_contenttype = '',
        $sac_inprogress = false
    ) {
        // Perform the deposit
        $sac_curl = $this->curlInit($sac_url, $sac_u, $sac_p);

        curl_setopt($sac_curl, CURLOPT_POST, true);

        $headers = array();
        array_push($headers, self::SAL_USER_AGENT);
        array_push($headers, "Content-MD5: " . md5_file($sac_fname));
        if (!empty($sac_obo)) {
            array_push($headers, "On-Behalf-Of: " . $sac_obo);
        }
        if (!empty($sac_packaging)) {
            array_push($headers, "Packaging: " . $sac_packaging);
        }
        if (!empty($sac_contenttype)) {
            array_push($headers, "Content-Type: " . $sac_contenttype);
        }
        array_push($headers, "Content-Length: " . filesize($sac_fname));
        if ($sac_inprogress) {
            array_push($headers, "In-Progress: true");
        } else {
            array_push($headers, "In-Progress: false");
        }

            // Set the Content-Disposition header
            $index = strpos(strrev($sac_fname), '/');
        if ($index !== false) {
            $index = strlen($sac_fname) - $index;
            $sac_fname_trimmed = substr($sac_fname, $index);
        } else {
            $sac_fname_trimmed = $sac_fname;
        }
            array_push($headers, "Content-Disposition: attachment; filename=" . $sac_fname_trimmed);
            curl_setopt($sac_curl, CURLOPT_READDATA, fopen($sac_fname, 'rb'));
            curl_setopt($sac_curl, CURLOPT_HTTPHEADER, $headers);

            $sac_resp = curl_exec($sac_curl);
            $sac_status = curl_getinfo($sac_curl, CURLINFO_HTTP_CODE);
            curl_close($sac_curl);

            // Parse the result
            $sac_dresponse = new SWORDAPPEntry($sac_status, $sac_resp);

            // Was it a successful result?
        if (($sac_status >= 200) && ($sac_status < 300)) {
            try {
                // Get the deposit results
                $sac_xml = @new \SimpleXMLElement($sac_resp);
                $sac_ns = $sac_xml->getNamespaces(true);

                // Build the deposit response object
                $sac_dresponse->buildhierarchy($sac_xml, $sac_ns);
            } catch (\Exception $e) {
                throw new \Exception("Error parsing response entry (" . $e->getMessage() . ")");
            }
        } else {
            try {
                // Parse the result
                $sac_dresponse = new SWORDAPPErrorDocument($sac_status, $sac_resp);

                // Get the deposit results
                $sac_xml = @new \SimpleXMLElement($sac_resp);
                $sac_ns = $sac_xml->getNamespaces(true);

                // Build the deposit response object
                $sac_dresponse->buildhierarchy($sac_xml, $sac_ns);
            } catch (\Exception $e) {
                throw new \Exception("Error parsing error document (" . $e->getMessage() . ")");
            }
        }

            // Return the deposit object
            return $sac_dresponse;
    }

    /**
     * Deposit a multipart package
     *
     * @param  string $sac_url
     * @param  string $sac_u
     * @param  string $sac_p
     * @param  string $sac_obo
     * @param  string $sac_package
     * @param  bool $sac_inprogress (optional)
     * @return SWORDAPPEntry|SWORDAPPErrorDocument
     * @throws \Exception
     */
    public function depositMultipart($sac_url, $sac_u, $sac_p, $sac_obo, $sac_package, $sac_inprogress = false)
    {
        try {
            return $this->depositMultipartByMethod(
                $sac_url,
                $sac_u,
                $sac_p,
                $sac_obo,
                $sac_package,
                "POST",
                $sac_inprogress
            );
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Function to create a resource by depositing an Atom entry
     *
     * @param  string $sac_url
     * @param  string $sac_u
     * @param  string $sac_p
     * @param  string $sac_obo
     * @param  string $sac_fname
     * @param  bool $sac_inprogress (optional)
     * @return SWORDAPPEntry|SWORDAPPErrorDocument
     */
    public function depositAtomEntry($sac_url, $sac_u, $sac_p, $sac_obo, $sac_fname, $sac_inprogress = false)
    {
        return $this->depositAtomEntryByMethod($sac_url, $sac_u, $sac_p, $sac_obo, 'POST', $sac_fname, $sac_inprogress);
    }

    /**
     * Complete an incomplete deposit by posting the In-Progress header of false to an SE-IRI
     *
     * @param  string $sac_url
     * @param  string $sac_u
     * @param  string $sac_p
     * @param  string $sac_obo
     * @return SWORDAPPResponse
     */
    public function completeIncompleteDeposit($sac_url, $sac_u, $sac_p, $sac_obo)
    {
        // Perform the deposit
        $sac_curl = $this->curlInit($sac_url, $sac_u, $sac_p);

        curl_setopt($sac_curl, CURLOPT_POST, true);

        $headers = array();
        array_push($headers, self::SAL_USER_AGENT);
        if (!empty($sac_obo)) {
            array_push($headers, "On-Behalf-Of: " . $sac_obo);
        }
        array_push($headers, "Content-Length: 0");
        array_push($headers, "In-Progress: false");

        curl_setopt($sac_curl, CURLOPT_HTTPHEADER, $headers);

        $sac_resp = curl_exec($sac_curl);
        $sac_status = curl_getinfo($sac_curl, CURLINFO_HTTP_CODE);
        curl_close($sac_curl);

        // Parse the result
        $sac_response = new SWORDAPPResponse($sac_status, $sac_resp);

        // Return the response
        return $sac_response;
    }

    /**
     * Function to retrieve the content of a container
     *
     * @param  $sac_url
     * @param  $sac_u
     * @param  $sac_p
     * @param  $sac_obo
     * @param  string $sac_accept_packaging
     * @return mixed
     */
    public function retrieveContent($sac_url, $sac_u, $sac_p, $sac_obo, $sac_accept_packaging = "")
    {
        // Retrieve the content
        $sac_curl = $this->curlInit($sac_url, $sac_u, $sac_p);

        $headers = array();
        array_push($headers, self::SAL_USER_AGENT);
        if (!empty($sac_obo)) {
            array_push($headers, "X-On-Behalf-Of: " . $sac_obo);
        }
        if (!empty($sac_accept_packaging)) {
            array_push($headers, "Accept-Packaging: " . $sac_accept_packaging);
        }
        curl_setopt($sac_curl, CURLOPT_HTTPHEADER, $headers);
        $sac_resp = curl_exec($sac_curl);
        curl_close($sac_curl);

        // Return the response
        return $sac_resp;
    }

    /**
     * Function to retrieve the entry content of a container
     *
     * @param  string $sac_url
     * @param  string $sac_u
     * @param  string $sac_p
     * @param  string $sac_obo
     * @param  string $sac_accept_packaging (optional)
     * @return SWORDAPPEntry|SWORDAPPErrorDocument
     * @throws \Exception
     */
    public function retrieveDepositReceipt($sac_url, $sac_u, $sac_p, $sac_obo, $sac_accept_packaging = '')
    {
        // Retrieve the content
        $sac_curl = $this->curlInit($sac_url, $sac_u, $sac_p);

        $headers = array();
        array_push($headers, self::SAL_USER_AGENT);
        if (!empty($sac_obo)) {
            array_push($headers, "X-On-Behalf-Of: " . $sac_obo);
        }
        if (!empty($sac_accept_packaging)) {
            array_push($headers, "Accept-Packaging: " . $sac_accept_packaging);
        }
        curl_setopt($sac_curl, CURLOPT_HTTPHEADER, $headers);
        $sac_resp = curl_exec($sac_curl);
        $sac_status = curl_getinfo($sac_curl, CURLINFO_HTTP_CODE);
        curl_close($sac_curl);

        // Parse the result
        $sac_dresponse = new SWORDAPPEntry($sac_status, $sac_resp);

        // Parse the result
        if (($sac_status >= 200) && ($sac_status < 300)) {
            try {
                // Get the deposit results
                $sac_xml = @new \SimpleXMLElement($sac_resp);
                $sac_ns = $sac_xml->getNamespaces(true);

                // Build the deposit response object
                $sac_dresponse->buildhierarchy($sac_xml, $sac_ns);
            } catch (\Exception $e) {
                throw new \Exception("Error parsing response entry (" . $e->getMessage() . ")");
            }
        } else {
            try {
                // Parse the result
                $sac_dresponse = new SWORDAPPErrorDocument($sac_status, $sac_resp);

                // Get the deposit results
                $sac_xml = @new \SimpleXMLElement($sac_resp);
                $sac_ns = $sac_xml->getNamespaces(true);

                // Build the deposit response object
                $sac_dresponse->buildhierarchy($sac_xml, $sac_ns);
            } catch (\Exception $e) {
                throw new \Exception("Error parsing error document (" . $e->getMessage() . ")");
            }
        }

            // Return the deposit object
            return $sac_dresponse;
    }

    /**
     * Replace the file content of a resource
     *
     * @param  string $sac_url
     * @param  string $sac_u
     * @param  string $sac_p
     * @param  string $sac_obo
     * @param  string $sac_fname
     * @param  string $sac_packaging (optional)
     * @param  string $sac_contenttype (optional)
     * @param  bool $sac_metadata_relevant (optional)
     * @return int
     * @throws \Exception
     */
    public function replaceFileContent(
        $sac_url,
        $sac_u,
        $sac_p,
        $sac_obo,
        $sac_fname,
        $sac_packaging = '',
        $sac_contenttype = '',
        $sac_metadata_relevant = false
    ) {
        // Perform the deposit
        $sac_curl = $this->curlInit($sac_url, $sac_u, $sac_p);

        curl_setopt($sac_curl, CURLOPT_PUT, true);

        $headers = array();
        array_push($headers, self::SAL_USER_AGENT);
        array_push($headers, "Content-MD5: " . md5_file($sac_fname));
        if (!empty($sac_obo)) {
            array_push($headers, "On-Behalf-Of: " . $sac_obo);
        }
        if (!empty($sac_packaging)) {
            array_push($headers, "Packaging: " . $sac_packaging);
        }
        if (!empty($sac_contenttype)) {
            array_push($headers, "Content-Type: " . $sac_contenttype);
        }
        if ($sac_metadata_relevant) {
            array_push($headers, "Metadata-Relevant: true");
        } else {
            array_push($headers, "Metadata-Relevant: false");
        }

            // Set the Content-Disposition header
            $index = strpos(strrev($sac_fname), '/');
        if ($index !== false) {
            $index = strlen($sac_fname) - $index;
            $sac_fname_trimmed = substr($sac_fname, $index);
        } else {
            $sac_fname_trimmed = $sac_fname;
        }
            array_push($headers, "Content-Disposition: attachment; filename=" . $sac_fname_trimmed);
            curl_setopt($sac_curl, CURLOPT_INFILE, fopen($sac_fname, 'rb'));
            curl_setopt($sac_curl, CURLOPT_INFILESIZE, filesize($sac_fname));
            curl_setopt($sac_curl, CURLOPT_HTTPHEADER, $headers);

            $sac_status = curl_getinfo($sac_curl, CURLINFO_HTTP_CODE);
            curl_close($sac_curl);

            // Was it a successful result?
        if ($sac_status != 204) {
            throw new \Exception("Error replacing file (HTTP code: " . $sac_status . ")");
        } else {
            return $sac_status;
        }
    }

    /**
     * Function to replace the metadata of a resource
     *
     * @param  string $sac_url
     * @param  string $sac_u
     * @param  string $sac_p
     * @param  string $sac_obo
     * @param  string $sac_fname
     * @param  bool $sac_inprogress (optional)
     * @return SWORDAPPEntry|SWORDAPPErrorDocument
     */
    public function replaceMetadata($sac_url, $sac_u, $sac_p, $sac_obo, $sac_fname, $sac_inprogress = false)
    {
        return $this->depositAtomEntryByMethod($sac_url, $sac_u, $sac_p, $sac_obo, 'PUT', $sac_fname, $sac_inprogress);
    }

    /**
     * Replace a multipart package
     *
     * @param  string $sac_url
     * @param  string $sac_u
     * @param  string $sac_p
     * @param  string $sac_obo
     * @param  string $sac_package
     * @param  bool $sac_inprogress (optional)
     * @return SWORDAPPEntry|SWORDAPPErrorDocument
     */
    public function replaceMetadataAndFile($sac_url, $sac_u, $sac_p, $sac_obo, $sac_package, $sac_inprogress = false)
    {
        return $this->depositMultipartByMethod(
            $sac_url,
            $sac_u,
            $sac_p,
            $sac_obo,
            $sac_package,
            'PUT',
            $sac_inprogress
        );
    }

    /**
     *  Add a an extra file to the media resource
     *
     * @param  string $sac_url
     * @param  string $sac_u
     * @param  string $sac_p
     * @param  string $sac_obo
     * @param  string $sac_fname
     * @param  string $sac_contenttype (optional)
     * @param  bool $sac_metadata_relevant (optional)
     * @return SWORDAPPEntry
     * @throws \Exception
     */
    public function addExtraFileToMediaResource(
        $sac_url,
        $sac_u,
        $sac_p,
        $sac_obo,
        $sac_fname,
        $sac_contenttype = '',
        $sac_metadata_relevant = false
    ) {
        // Perform the deposit
        $sac_curl = $this->curlInit($sac_url, $sac_u, $sac_p);

        curl_setopt($sac_curl, CURLOPT_POST, true);

        $headers = array();
        array_push($headers, self::SAL_USER_AGENT);
        array_push($headers, "Content-MD5: " . md5_file($sac_fname));
        if (!empty($sac_obo)) {
            array_push($headers, "On-Behalf-Of: " . $sac_obo);
        }
        if (!empty($sac_contenttype)) {
            array_push($headers, "Content-Type: " . $sac_contenttype);
        }
        if ($sac_metadata_relevant) {
            array_push($headers, "Metadata-Relevant: true");
        } else {
            array_push($headers, "Metadata-Relevant: false");
        }
            array_push($headers, "Content-Length: " . filesize($sac_fname));

            // Set the Content-Disposition header
            $index = strpos(strrev($sac_fname), '/');
        if ($index !== false) {
            $index = strlen($sac_fname) - $index;
            $sac_fname_trimmed = substr($sac_fname, $index);
        } else {
            $sac_fname_trimmed = $sac_fname;
        }
            array_push($headers, "Content-Disposition: attachment; filename=" . $sac_fname_trimmed);

            curl_setopt($sac_curl, CURLOPT_READDATA, fopen($sac_fname, 'rb'));
            curl_setopt($sac_curl, CURLOPT_HTTPHEADER, $headers);

            $sac_resp = curl_exec($sac_curl);
            $sac_status = curl_getinfo($sac_curl, CURLINFO_HTTP_CODE);
            curl_close($sac_curl);

            // Parse the result
            $sac_dresponse = new SWORDAPPEntry($sac_status, $sac_resp);

            // Was it a successful result?
        if (($sac_status >= 200) && ($sac_status < 300)) {
            try {
                // Get the deposit results
                //$sac_xml = @new \SimpleXMLElement($sac_resp);
                //$sac_ns = $sac_xml->getNamespaces(true);

                // Build the deposit response object
                //$sac_dresponse->buildhierarchy($sac_xml, $sac_ns);
            } catch (\Exception $e) {
                throw new \Exception("Error parsing response entry (" . $e->getMessage() . ")");
            }
        } else {
            try {
                // Parse the result
                //$sac_dresponse = new SWORDAPPErrorDocument($sac_status, $sac_resp);

                // Get the deposit results
                //$sac_xml = @new \SimpleXMLElement($sac_resp);
                //$sac_ns = $sac_xml->getNamespaces(true);

                // Build the deposit response object
                //$sac_dresponse->buildhierarchy($sac_xml, $sac_ns);
            } catch (\Exception $e) {
                throw new \Exception("Error parsing error document (" . $e->getMessage() . ")");
            }
        }

            return $sac_dresponse;
    }

    /**
     * Add a new package
     *
     * @param  string $sac_url
     * @param  string $sac_u
     * @param  string $sac_p
     * @param  string $sac_obo
     * @param  string $sac_fname
     * @param  string $sac_packaging (optional)
     * @param  $sac_contenttype (optional)
     * @param  bool $sac_inprogress (optional)
     * @return SWORDAPPEntry|SWORDAPPErrorDocument
     */
    public function addExtraPackage(
        $sac_url,
        $sac_u,
        $sac_p,
        $sac_obo,
        $sac_fname,
        $sac_packaging = '',
        $sac_contenttype = '',
        $sac_inprogress = false
    ) {
        return $this->deposit(
            $sac_url,
            $sac_u,
            $sac_p,
            $sac_obo,
            $sac_fname,
            $sac_packaging,
            $sac_contenttype,
            $sac_inprogress
        );
    }

    /**
     * Add a new Atom entry
     *
     * @param  string $sac_url
     * @param  string $sac_u
     * @param  string $sac_p
     * @param  string $sac_obo
     * @param  string $sac_fname
     * @param  bool $sac_inprogress (optional)
     * @return SWORDAPPEntry|SWORDAPPErrorDocument
     */
    public function addExtraAtomEntry($sac_url, $sac_u, $sac_p, $sac_obo, $sac_fname, $sac_inprogress = false)
    {
        return $this->depositAtomEntryByMethod($sac_url, $sac_u, $sac_p, $sac_obo, "POST", $sac_fname, $sac_inprogress);
    }

    /**
     * Add a new multipart package
     *
     * @param  string $sac_url
     * @param  string $sac_u
     * @param  string $sac_p
     * @param  string $sac_obo
     * @param  string $sac_package
     * @param  bool $sac_inprogress (optional)
     * @return SWORDAPPEntry|SWORDAPPErrorDocument
     */
    public function addExtraMultipartPackage($sac_url, $sac_u, $sac_p, $sac_obo, $sac_package, $sac_inprogress = false)
    {
        return $this->depositMultipartByMethod(
            $sac_url,
            $sac_u,
            $sac_p,
            $sac_obo,
            $sac_package,
            'POST',
            $sac_inprogress
        );
    }

    /**
     * Function to delete a container (object)
     *
     * @param  string $sac_url
     * @param  string $sac_u
     * @param  string $sac_p
     * @param  string $sac_obo
     * @return SWORDAPPResponse
     */
    public function deleteContainer($sac_url, $sac_u, $sac_p, $sac_obo)
    {
        // Perform the deposit
        $sac_curl = $this->curlInit($sac_url, $sac_u, $sac_p);

        curl_setopt($sac_curl, CURLOPT_CUSTOMREQUEST, "DELETE");

        $headers = array();
        array_push($headers, self::SAL_USER_AGENT);
        if (!empty($sac_obo)) {
            array_push($headers, "On-Behalf-Of: " . $sac_obo);
        }

        curl_setopt($sac_curl, CURLOPT_HTTPHEADER, $headers);

        $sac_resp = curl_exec($sac_curl);
        $sac_status = curl_getinfo($sac_curl, CURLINFO_HTTP_CODE);
        curl_close($sac_curl);

        return new SWORDAPPResponse($sac_status, $sac_resp);
    }

    /**
     * Function to delete the content of a resource
     *
     * @param  string $sac_url
     * @param  string $sac_u
     * @param  string $sac_p
     * @param  string $sac_obo
     * @return SWORDAPPResponse
     */
    public function deleteResourceContent($sac_url, $sac_u, $sac_p, $sac_obo)
    {
        return $this->deleteContainer($sac_url, $sac_u, $sac_p, $sac_obo);
    }

    /**
     * Function to retrieve an Atom statement
     *
     * @param  string $sac_url
     * @param  string $sac_u
     * @param  string $sac_p
     * @param  string $sac_obo
     * @return SWORDAPPStatement
     * @throws \Exception
     */
    public function retrieveAtomStatement($sac_url, $sac_u, $sac_p, $sac_obo)
    {
        // Get the Atom statement
        $sac_curl = $this->curlInit($sac_url, $sac_u, $sac_p);

        $headers = array();
        array_push($headers, self::SAL_USER_AGENT);
        if (!empty($sac_obo)) {
            array_push($headers, "X-On-Behalf-Of: " . $sac_obo);
        }
        curl_setopt($sac_curl, CURLOPT_HTTPHEADER, $headers);
        $sac_resp = curl_exec($sac_curl);
        $sac_status = curl_getinfo($sac_curl, CURLINFO_HTTP_CODE);
        curl_close($sac_curl);

        // Parse the result
        if ($sac_status == 200) {
            try {
                $sac_atomstatement = new SWORDAPPStatement($sac_status, $sac_resp);
            } catch (\Exception $e) {
                throw new \Exception("Error parsing statement (" . $e->getMessage() . ")");
            }
        } else {
            $sac_atomstatement = new SWORDAPPStatement($sac_url, $sac_status);
        }

            // Return the atom statement object
            return $sac_atomstatement;
    }

    /**
     * Function to retrieve an OAI-ORE statement - this just returns the xml, it does not marshall it into an object.
     *
     * @param  string $sac_url
     * @param  string $sac_u
     * @param  string $sac_p
     * @param  string $sac_obo
     * @return mixed
     */
    public function retrieveOAIOREStatement($sac_url, $sac_u, $sac_p, $sac_obo)
    {
        // Get the OAI-ORE statement
        $sac_curl = $this->curlInit($sac_url, $sac_u, $sac_p);

        $headers = array();
        array_push($headers, self::SAL_USER_AGENT);
        if (!empty($sac_obo)) {
            array_push($headers, "X-On-Behalf-Of: " . $sac_obo);
        }
        curl_setopt($sac_curl, CURLOPT_HTTPHEADER, $headers);
        $sac_resp = curl_exec($sac_curl);
        curl_close($sac_curl);

        // Return the result
        return $sac_resp;
    }

    /**
     * Generic private method to initalise a curl transaction
     *
     * @param  string $sac_url
     * @param  string $sac_user
     * @param  string $sac_password
     * @return resource
     */
    private function curlInit($sac_url, $sac_user, $sac_password)
    {
        // Initialise the curl object
        $sac_curl = curl_init();

        // Return the content from curl, rather than outputting it
        curl_setopt($sac_curl, CURLOPT_RETURNTRANSFER, true);

        // Set the debug option
        curl_setopt($sac_curl, CURLOPT_VERBOSE, $this->debug);

        // Set the URL to connect to
        curl_setopt($sac_curl, CURLOPT_URL, $sac_url);

        // If required, set authentication
        if (!empty($sac_user) && !empty($sac_password)) {
            curl_setopt($sac_curl, CURLOPT_USERPWD, $sac_user . ":" . $sac_password);
        }

        // Set user-specified curl opts
        foreach ($this->curl_opts as $opt => $val) {
            curl_setopt($sac_curl, $opt, $val);
        }

        // Return the initalised curl object
        return $sac_curl;
    }

    /**
     * A method for multipart deposit - method can be set - POST or PUT
     *
     * @param  string $sac_url
     * @param  string $sac_u
     * @param  string $sac_p
     * @param  string $sac_obo
     * @param  string $sac_package
     * @param  string $sac_method
     * @param  bool $sac_inprogress (optional)
     * @return SWORDAPPEntry|SWORDAPPErrorDocument
     * @throws \Exception
     */
    private function depositMultipartByMethod(
        $sac_url,
        $sac_u,
        $sac_p,
        $sac_obo,
        $sac_package,
        $sac_method,
        $sac_inprogress = false
    ) {
        $sac_curl = $this->curlInit($sac_url, $sac_u, $sac_p);

        $headers = array();

        if ($sac_inprogress) {
            array_push($headers, "In-Progress: true");
        } else {
            array_push($headers, "In-Progress: false");
        }

        if (!empty($sac_obo)) {
            array_push($headers, "On-Behalf-Of: " . $sac_obo);
        }

        array_push(
            $headers,
            "Content-Type: multipart/related; boundary=\"===============SWORDPARTS==\"; type=\"application/atom+xml\""
        );

        // Set the appropriate method
        if ($sac_method == "PUT") {
            curl_setopt($sac_curl, CURLOPT_PUT, true);
            curl_setopt($sac_curl, CURLOPT_INFILE, fopen($sac_package, 'rb'));
            curl_setopt($sac_curl, CURLOPT_INFILESIZE, filesize($sac_package));
        } else {
            curl_setopt($sac_curl, CURLOPT_POST, true);
            curl_setopt($sac_curl, CURLOPT_CONNECTTIMEOUT, 30);
            curl_setopt($sac_curl, CURLOPT_LOW_SPEED_LIMIT, 1);
            curl_setopt($sac_curl, CURLOPT_LOW_SPEED_TIME, 180);
            curl_setopt($sac_curl, CURLOPT_NOSIGNAL, 1);

            array_push($headers, "Content-Length: " . filesize($sac_package));

            // Instantiate the streaming class
            $my_class_inst = new StreamingClass();
            $my_class_inst->data = fopen($sac_package, "r");
            curl_setopt($sac_curl, CURLOPT_READFUNCTION, array($my_class_inst, 'streamFunction'));
        }

        curl_setopt($sac_curl, CURLOPT_HTTPHEADER, $headers);

        $sac_resp = curl_exec($sac_curl);
        $sac_status = curl_getinfo($sac_curl, CURLINFO_HTTP_CODE);

        curl_close($sac_curl);

        // Parse the result
        $sac_dresponse = new SWORDAPPEntry($sac_status, $sac_resp);

        // Was it a successful result?
        if (($sac_status >= 200) && ($sac_status < 300)) {
            try {
                // Get the deposit results
                $sac_xml = @new \SimpleXMLElement($sac_resp);
                $sac_ns = $sac_xml->getNamespaces(true);

                // Build the deposit response object
                $sac_dresponse->buildhierarchy($sac_xml, $sac_ns);
            } catch (\Exception $e) {
                throw new \Exception("Error parsing response entry (" . $e->getMessage() . ")");
            }
        } else {
            try {
                // Parse the result
                $sac_dresponse = new SWORDAPPErrorDocument($sac_status, $sac_resp);

                // Get the deposit results
                $sac_xml = @new \SimpleXMLElement($sac_resp);
                $sac_ns = $sac_xml->getNamespaces(true);

                // Build the deposit response object
                $sac_dresponse->buildhierarchy($sac_xml, $sac_ns);
            } catch (\Exception $e) {
                throw new \Exception("Error parsing error document (" . $e->getMessage() . ")");
            }
        }

        // Return the deposit object
        return $sac_dresponse;
    }

    /**
     * Function to deposit an Atom entry
     *
     * @param  string $sac_url
     * @param  string $sac_u
     * @param  string $sac_p
     * @param  string $sac_obo
     * @param  string $sac_method
     * @param  string $sac_fname
     * @param  bool $sac_inprogress (optional)
     * @return SWORDAPPEntry|SWORDAPPErrorDocument
     * @throws \Exception
     */
    private function depositAtomEntryByMethod(
        $sac_url,
        $sac_u,
        $sac_p,
        $sac_obo,
        $sac_method,
        $sac_fname,
        $sac_inprogress = false
    ) {
        // Perform the deposit
        $sac_curl = $this->curlInit($sac_url, $sac_u, $sac_p);

        $headers = array();
        array_push($headers, self::SAL_USER_AGENT);
        if (!empty($sac_obo)) {
            array_push($headers, "On-Behalf-Of: " . $sac_obo);
        }
        array_push($headers, "Content-Type: application/atom+xml;type=entry");
        if ($sac_inprogress) {
            array_push($headers, "In-Progress: true");
        } else {
            array_push($headers, "In-Progress: false");
        }

        // Set the appropriate method
        if ($sac_method == "PUT") {
            curl_setopt($sac_curl, CURLOPT_PUT, true);
            curl_setopt($sac_curl, CURLOPT_INFILE, fopen($sac_fname, 'rb'));
            curl_setopt($sac_curl, CURLOPT_INFILESIZE, filesize($sac_fname));
        } else {
            curl_setopt($sac_curl, CURLOPT_POST, true);
            curl_setopt($sac_curl, CURLOPT_READDATA, fopen($sac_fname, 'rb'));
            array_push($headers, "Content-Length: " . filesize($sac_fname));
        }

        curl_setopt($sac_curl, CURLOPT_HTTPHEADER, $headers);

        $sac_resp = curl_exec($sac_curl);
        $sac_status = curl_getinfo($sac_curl, CURLINFO_HTTP_CODE);
        curl_close($sac_curl);

        // Parse the result
        $sac_dresponse = new SWORDAPPEntry($sac_status, $sac_resp);

        // Was it a successful result?
        if (($sac_status >= 200) && ($sac_status < 300)) {
            try {
                // Get the deposit results
                $sac_xml = @new \SimpleXMLElement($sac_resp);
                $sac_ns = $sac_xml->getNamespaces(true);

                // Build the deposit response object
                $sac_dresponse->buildhierarchy($sac_xml, $sac_ns);
            } catch (\Exception $e) {
                throw new \Exception("Error parsing response entry (" . $e->getMessage() . ")");
            }
        } else {
            try {
                // Parse the result
                $sac_dresponse = new SWORDAPPErrorDocument($sac_status, $sac_resp);

                // Get the deposit results
                $sac_xml = @new \SimpleXMLElement($sac_resp);
                $sac_ns = $sac_xml->getNamespaces(true);

                // Build the deposit response object
                $sac_dresponse->buildhierarchy($sac_xml, $sac_ns);
            } catch (\Exception $e) {
                throw new \Exception("Error parsing error document (" . $e->getMessage() . ")");
            }
        }

        // Return the deposit object
        return $sac_dresponse;
    }


    /**
     * Request a URI with the specified credentials, and on-behalf-of the specified user.
     * This is not specifically for SWORD, but for retrieving other associated URIs
     *
     * @param  string $sac_url
     * @param  string $sac_u
     * @param  string $sac_p
     * @param  string $sac_obo
     * @return mixed
     */
    private function get($sac_url, $sac_u, $sac_p, $sac_obo)
    {
        // Get the service document
        $sac_curl = $this->curlInit($sac_url, $sac_u, $sac_p);

        $headers = array();
        array_push($headers, self::SAL_USER_AGENT);
        if (!empty($sac_obo)) {
            array_push($headers, "X-On-Behalf-Of: " . $sac_obo);
        }
        curl_setopt($sac_curl, CURLOPT_HTTPHEADER, $headers);
        $sac_resp = curl_exec($sac_curl);
        curl_close($sac_curl);

        // Return the response
        return $sac_resp;
    }
}
