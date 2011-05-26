<?php

require("swordappservicedocument.php");
require("swordappentry.php");
require("swordappstatement.php");
require("swordapperrordocument.php");
require("swordapplibraryuseragent.php");
require("stream.php");
require_once("utils.php");

class SWORDAPPClient {

    private $debug = false;

    // Request a Service Document from the specified url, with the specified credentials,
    // and on-behalf-of the specified user.
    function servicedocument($sac_url, $sac_u, $sac_p, $sac_obo) {
        // Get the service document
        $sac_curl = $this->curl_init($sac_url, $sac_u, $sac_p);

        $headers = array();
        global $sal_useragent;
        array_push($headers, $sal_useragent);
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
            } catch (Exception $e) {
                throw new Exception("Error parsing service document (" . $e->getMessage() . ")");
            }
        } else {
            $sac_sdresponse = new SWORDAPPServiceDocument($sac_url, $sac_status);
        }
        
        // Return the Service Document object
        return $sac_sdresponse;
    }

    // Perform a deposit to the specified url, with the specified credentials,
    // on-behalf-of the specified user, and with the given file and formatnamespace and noop setting
    function deposit($sac_url, $sac_u, $sac_p, $sac_obo, $sac_fname,
                     $sac_packaging= '', $sac_contenttype = '', $sac_inprogress = false) {
        // Perform the deposit
        $sac_curl = $this->curl_init($sac_url, $sac_u, $sac_p);

        curl_setopt($sac_curl, CURLOPT_POST, true);

        $headers = array();
        global $sal_useragent;
        array_push($headers, $sal_useragent);
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
        array_push($headers, "Content-Disposition: filename=" . $sac_fname_trimmed);
        curl_setopt($sac_curl, CURLOPT_READDATA, fopen($sac_fname, 'rb'));
        curl_setopt($sac_curl, CURLOPT_HTTPHEADER, $headers);

        $sac_resp = curl_exec($sac_curl);
        $sac_status = curl_getinfo($sac_curl, CURLINFO_HTTP_CODE);
        curl_close($sac_curl);

        // Parse the result
        $sac_dresponse = new SWORDAPPEntry($sac_status, $sac_resp);

        // Was it a successful result?
        if (($sac_status >= 200) || ($sac_status < 300)) {
            try {
                // Get the deposit results
                $sac_xml = @new SimpleXMLElement($sac_resp);
                $sac_ns = $sac_xml->getNamespaces(true);

                // Build the deposit response object
                $sac_dresponse->buildhierarchy($sac_xml, $sac_ns);
            } catch (Exception $e) {
                throw new Exception("Error parsing response entry (" . $e->getMessage() . ")");
            }
        } else {
            try {
                // Parse the result
                $sac_dresponse = new SWORDAPPErrorDocument($sac_status, $sac_resp);

                // Get the deposit results
                $sac_xml = @new SimpleXMLElement($sac_resp);
                $sac_ns = $sac_xml->getNamespaces(true);

                // Build the deposit response object
                $sac_dresponse->buildhierarchy($sac_xml, $sac_ns);
            } catch (Exception $e) {
                throw new Exception("Error parsing error document (" . $e->getMessage() . ")");
            }
        }

        // Return the deposit object
        return $sac_dresponse;
    }

    // Deposit a multipart package
    function depositMultipart($sac_url, $sac_u, $sac_p, $sac_obo, $sac_package,
                              $sac_packaging = '', $sac_inprogress = false) {
        $sac_curl = $this->curl_init($sac_url, $sac_u, $sac_p);

        // Instantiate the streaming class
        $my_class_inst = new StreamingClass();
        $my_class_inst->data = fopen($sac_package, "r");

        curl_setopt($sac_curl, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($sac_curl, CURLOPT_LOW_SPEED_LIMIT, 1);
        curl_setopt($sac_curl, CURLOPT_LOW_SPEED_TIME, 180);
        curl_setopt($sac_curl, CURLOPT_NOSIGNAL, 1);
        curl_setopt($sac_curl, CURLOPT_READFUNCTION, array($my_class_inst, "stream_function"));
        curl_setopt($sac_curl, CURLOPT_POST, true);

        if ($sac_inprogress) {
            $header[] = "In-Progress: true";
        } else {
            $header[] = "In-Progress: false";
        }

        if (!empty($sac_obo)) {
            $headers[] =  "On-Behalf-Of: " . $sac_obo;
        }

        $header[] = "Packaging: " . $sac_packaging;
        $header[] = "Content-Length: " . filesize($sac_package);
        $header[] = "Content-Type: multipart/related; boundary=\"===============SWORDPARTS==\"";

        curl_setopt($sac_curl, CURLOPT_HTTPHEADER, $header);

        $sac_resp = curl_exec ($sac_curl);
        $sac_status = curl_getinfo($sac_curl, CURLINFO_HTTP_CODE);

        curl_close($sac_curl);

        // Parse the result
        $sac_dresponse = new SWORDAPPEntry($sac_status, $sac_resp);

        // Was it a successful result?
        if (($sac_status >= 200) || ($sac_status < 300)) {
            try {
                // Get the deposit results
                $sac_xml = @new SimpleXMLElement($sac_resp);
                $sac_ns = $sac_xml->getNamespaces(true);

                // Build the deposit response object
                $sac_dresponse->buildhierarchy($sac_xml, $sac_ns);
            } catch (Exception $e) {
                throw new Exception("Error parsing response entry (" . $e->getMessage() . ")");
            }
        } else {
            try {
                // Parse the result
                $sac_dresponse = new SWORDAPPErrorDocument($sac_status, $sac_resp);

                // Get the deposit results
                $sac_xml = @new SimpleXMLElement($sac_resp);
                $sac_ns = $sac_xml->getNamespaces(true);

                // Build the deposit response object
                $sac_dresponse->buildhierarchy($sac_xml, $sac_ns);
            } catch (Exception $e) {
                throw new Exception("Error parsing error document (" . $e->getMessage() . ")");
            }
        }

        // Return the deposit object
        return $sac_dresponse;
    }

    // Function to create a resource by depositing an Atom entry
    function depositAtomEntry($sac_url, $sac_u, $sac_p, $sac_obo, $sac_fname, $sac_inprogress = false) {
        // Perform the deposit
        $sac_curl = $this->curl_init($sac_url, $sac_u, $sac_p);

        curl_setopt($sac_curl, CURLOPT_POST, true);

        $headers = array();
        global $sal_useragent;
        array_push($headers, $sal_useragent);
        if (!empty($sac_obo)) {
            array_push($headers, "On-Behalf-Of: " . $sac_obo);
        }
        array_push($headers, "Content-Type: application/atom+xml;type=entry");
        array_push($headers, "Content-Length: " . filesize($sac_fname));
        if ($sac_inprogress) {
            array_push($headers, "In-Progress: true");
        } else {
            array_push($headers, "In-Progress: false");
        }

        curl_setopt($sac_curl, CURLOPT_READDATA, fopen($sac_fname, 'rb'));
        curl_setopt($sac_curl, CURLOPT_HTTPHEADER, $headers);

        $sac_resp = curl_exec($sac_curl);
        $sac_status = curl_getinfo($sac_curl, CURLINFO_HTTP_CODE);
        curl_close($sac_curl);

        // Parse the result
        $sac_dresponse = new SWORDAPPEntry($sac_status, $sac_resp);

        // Was it a successful result?
        if (($sac_status >= 200) || ($sac_status < 300)) {
            try {
                // Get the deposit results
                $sac_xml = @new SimpleXMLElement($sac_resp);
                $sac_ns = $sac_xml->getNamespaces(true);

                // Build the deposit response object
                $sac_dresponse->buildhierarchy($sac_xml, $sac_ns);
            } catch (Exception $e) {
                throw new Exception("Error parsing response entry (" . $e->getMessage() . ")");
            }
        } else {
            try {
                // Parse the result
                $sac_dresponse = new SWORDAPPErrorDocument($sac_status, $sac_resp);

                // Get the deposit results
                $sac_xml = @new SimpleXMLElement($sac_resp);
                $sac_ns = $sac_xml->getNamespaces(true);

                // Build the deposit response object
                $sac_dresponse->buildhierarchy($sac_xml, $sac_ns);
            } catch (Exception $e) {
                throw new Exception("Error parsing error document (" . $e->getMessage() . ")");
            }
        }

        // Return the deposit object
        return $sac_dresponse;
    }

    // Complete an incomplete deposit by posting the In-Progress header of false to an SE-IRI
    function completeIncompleteDeposit($sac_url, $sac_u, $sac_p, $sac_obo) {
        // Perform the deposit
        $sac_curl = $this->curl_init($sac_url, $sac_u, $sac_p);

        curl_setopt($sac_curl, CURLOPT_POST, true);

        $headers = array();
        global $sal_useragent;
        array_push($headers, $sal_useragent);
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
        $sac_dresponse = new SWORDAPPEntry($sac_status, $sac_resp);

        // Was it a successful result?
        if (($sac_status >= 200) || ($sac_status < 300)) {
            try {
                // Get the deposit results
                $sac_xml = @new SimpleXMLElement($sac_resp);
                $sac_ns = $sac_xml->getNamespaces(true);

                // Build the deposit response object
                $sac_dresponse->buildhierarchy($sac_xml, $sac_ns);
            } catch (Exception $e) {
                throw new Exception("Error parsing response entry (" . $e->getMessage() . ")");
            }
        } else {
            try {
                // Parse the result
                $sac_dresponse = new SWORDAPPErrorDocument($sac_status, $sac_resp);

                // Get the deposit results
                $sac_xml = @new SimpleXMLElement($sac_resp);
                $sac_ns = $sac_xml->getNamespaces(true);

                // Build the deposit response object
                $sac_dresponse->buildhierarchy($sac_xml, $sac_ns);
            } catch (Exception $e) {
                throw new Exception("Error parsing error document (" . $e->getMessage() . ")");
            }
        }

        // Return the deposit object
        return $sac_dresponse;
    }

    // Function to retrieve the content of a container
    function retrieveContentEntry($sac_url, $sac_u, $sac_p, $sac_obo, $sac_accept_packaging = "") {
        // Retrieve the content
        $sac_curl = $this->curl_init($sac_url, $sac_u, $sac_p);

        $headers = array();
        global $sal_useragent;
        array_push($headers, $sal_useragent);
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
        if (($sac_status >= 200) || ($sac_status < 300)) {
            try {
                // Get the deposit results
                $sac_xml = @new SimpleXMLElement($sac_resp);
                $sac_ns = $sac_xml->getNamespaces(true);

                // Build the deposit response object
                $sac_dresponse->buildhierarchy($sac_xml, $sac_ns);
            } catch (Exception $e) {
                throw new Exception("Error parsing response entry (" . $e->getMessage() . ")");
            }
        } else {
            try {
                // Parse the result
                $sac_dresponse = new SWORDAPPErrorDocument($sac_status, $sac_resp);

                // Get the deposit results
                $sac_xml = @new SimpleXMLElement($sac_resp);
                $sac_ns = $sac_xml->getNamespaces(true);

                // Build the deposit response object
                $sac_dresponse->buildhierarchy($sac_xml, $sac_ns);
            } catch (Exception $e) {
                throw new Exception("Error parsing error document (" . $e->getMessage() . ")");
            }
        }

        // Return the deposit object
        return $sac_dresponse;
    }

    // Replace the file content of a resource
    function replaceFileContent($sac_url, $sac_u, $sac_p, $sac_obo, $sac_fname,
                                $sac_packaging= '', $sac_contenttype = '', $sac_metadata_relevant = false) {
        // Perform the deposit
        $sac_curl = $this->curl_init($sac_url, $sac_u, $sac_p);

        curl_setopt($sac_curl, CURLOPT_PUT, true);

        $headers = array();
        global $sal_useragent;
        array_push($headers, $sal_useragent);
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
        array_push($headers, "Content-Disposition: filename=" . $sac_fname_trimmed);
        curl_setopt($sac_curl, CURLOPT_INFILE, fopen($sac_fname, 'rb'));
        curl_setopt($sac_curl, CURLOPT_INFILESIZE, filesize($sac_fname));
        curl_setopt($sac_curl, CURLOPT_HTTPHEADER, $headers);

        $sac_resp = curl_exec($sac_curl);
        $sac_status = curl_getinfo($sac_curl, CURLINFO_HTTP_CODE);
        curl_close($sac_curl);

        // Was it a successful result?
        if ($sac_status != 204) {
            throw new Exception("Error replacing file (HTTP code: " . $sac_status . ")");
        }
    }

    // Function to replace the metadata of a resource
    function replaceMetadata($sac_url, $sac_u, $sac_p, $sac_obo, $sac_fname, $sac_inprogress = false) {
        // Perform the deposit
        $sac_curl = $this->curl_init($sac_url, $sac_u, $sac_p);

        curl_setopt($sac_curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($sac_curl, CURLOPT_PUT, true);

        $headers = array();
        global $sal_useragent;
        array_push($headers, $sal_useragent);
        if (!empty($sac_obo)) {
            array_push($headers, "On-Behalf-Of: " . $sac_obo);
        }
        array_push($headers, "Content-Type: application/atom+xml;type=entry");
        if ($sac_inprogress) {
            array_push($headers, "In-Progress: true");
        } else {
            array_push($headers, "In-Progress: false");
        }

        curl_setopt($sac_curl, CURLOPT_INFILE, fopen($sac_fname, 'rb'));
        curl_setopt($sac_curl, CURLOPT_INFILESIZE, filesize($sac_fname));
        curl_setopt($sac_curl, CURLOPT_HTTPHEADER, $headers);

        $sac_resp = curl_exec($sac_curl);
        $sac_status = curl_getinfo($sac_curl, CURLINFO_HTTP_CODE);
        curl_close($sac_curl);

        // Parse the result
        $sac_dresponse = new SWORDAPPEntry($sac_status, $sac_resp);

        // Was it a successful result?
        if (($sac_status >= 200) || ($sac_status < 300)) {
            try {
                // Get the deposit results
                $sac_xml = @new SimpleXMLElement($sac_resp);
                $sac_ns = $sac_xml->getNamespaces(true);

                // Build the deposit response object
                $sac_dresponse->buildhierarchy($sac_xml, $sac_ns);
            } catch (Exception $e) {
                throw new Exception("Error parsing response entry (" . $e->getMessage() . ")");
            }
        } else {
            try {
                // Parse the result
                $sac_dresponse = new SWORDAPPErrorDocument($sac_status, $sac_resp);

                // Get the deposit results
                $sac_xml = @new SimpleXMLElement($sac_resp);
                $sac_ns = $sac_xml->getNamespaces(true);

                // Build the deposit response object
                $sac_dresponse->buildhierarchy($sac_xml, $sac_ns);
            } catch (Exception $e) {
                throw new Exception("Error parsing error document (" . $e->getMessage() . ")");
            }
        }

        // Return the deposit object
        return $sac_dresponse;
    }


    // Function to delete a container (object)
    function deleteContainer($sac_url, $sac_u, $sac_p, $sac_obo) {
        // Perform the deposit
        $sac_curl = $this->curl_init($sac_url, $sac_u, $sac_p);

        curl_setopt($sac_curl, CURLOPT_CUSTOMREQUEST, "DELETE");

        $headers = array();
        global $sal_useragent;
        array_push($headers, $sal_useragent);
        if (!empty($sac_obo)) {
            array_push($headers, "On-Behalf-Of: " . $sac_obo);
        }

        curl_setopt($sac_curl, CURLOPT_HTTPHEADER, $headers);

        $sac_resp = curl_exec($sac_curl);
        $sac_status = curl_getinfo($sac_curl, CURLINFO_HTTP_CODE);
        curl_close($sac_curl);

        // Was it a successful result?
        if ($sac_status != 204) {
            throw new Exception("Error deleting container (HTTP code: " . $sac_status . ")");
        }
    }

    // Function to delete the content of a resource
    function deleteResourceContent($sac_url, $sac_u, $sac_p, $sac_obo) {
        $this->deleteContainer($sac_url, $sac_u, $sac_p, $sac_obo);
    }

    // Function to retrieve an Atom statement
    function retrieveAtomStatement($sac_url, $sac_u, $sac_p, $sac_obo) {
        // Get the Atom statement
        $sac_curl = $this->curl_init($sac_url, $sac_u, $sac_p);

        $headers = array();
        global $sal_useragent;
        array_push($headers, $sal_useragent);
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
            } catch (Exception $e) {
                throw new Exception("Error parsing statement (" . $e->getMessage() . ")");
            }
        } else {
            $sac_atomstatement = new SWORDAPPServiceDocument($sac_url, $sac_status);
        }

        // Return the Service Document object
        return $sac_atomstatement;
    }

    // Function to retrieve an OAI-ORE statement - this just returns the xml,
    // it does not marshall it into an object.
    function retrieveOAIOREStatement($sac_url, $sac_u, $sac_p, $sac_obo) {
        // Get the OAI-ORE statement
        $sac_curl = $this->curl_init($sac_url, $sac_u, $sac_p);

        $headers = array();
        global $sal_useragent;
        array_push($headers, $sal_useragent);
        if (!empty($sac_obo)) {
            array_push($headers, "X-On-Behalf-Of: " . $sac_obo);
        }
        curl_setopt($sac_curl, CURLOPT_HTTPHEADER, $headers);
        $sac_resp = curl_exec($sac_curl);
        $sac_status = curl_getinfo($sac_curl, CURLINFO_HTTP_CODE);
        curl_close($sac_curl);

        // Return the result
        return $sac_resp;
    }

    // Generic private method to initalise a curl transaction
    private function curl_init($sac_url, $sac_user, $sac_password) {
        // Initialise the curl object
        $sac_curl = curl_init();

        // Return the content from curl, rather than outputting it
        curl_setopt($sac_curl, CURLOPT_RETURNTRANSFER, true);

        // Set the debug option
        curl_setopt($sac_curl, CURLOPT_VERBOSE, $this->debug);

        // Set the URL to connect to
        curl_setopt($sac_curl, CURLOPT_URL, $sac_url);

        // If required, set authentication
        if(!empty($sac_user) && !empty($sac_password)) {
            curl_setopt($sac_curl, CURLOPT_USERPWD, $sac_user . ":" . $sac_password);
        }

        // Return the initalised curl object
        return $sac_curl;
    }
}

?>
