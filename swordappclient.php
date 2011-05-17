<?php

require("swordappservicedocument.php");
require("swordappentry.php");
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
		$sac_curl = curl_init();

		curl_setopt($sac_curl, CURLOPT_RETURNTRANSFER, true);
		if ($this->debug) curl_setopt($sac_curl, CURLOPT_VERBOSE, 1);

		curl_setopt($sac_curl, CURLOPT_URL, $sac_url);
		if(!empty($sac_u) && !empty($sac_p)) {
	        curl_setopt($sac_curl, CURLOPT_USERPWD, $sac_u . ":" . $sac_p);
	    }
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
		$sac_curl = curl_init();

		if ($this->debug) curl_setopt($sac_curl, CURLOPT_VERBOSE, 1);

		curl_setopt($sac_curl, CURLOPT_URL, $sac_url);
		curl_setopt($sac_curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($sac_curl, CURLOPT_POST, true);
		if(!empty($sac_u) && !empty($sac_p)) {
            curl_setopt($sac_curl, CURLOPT_USERPWD, $sac_u . ":" . $sac_p);
        }
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
        if ($index === false) {
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

    function depositMultipart($sac_url, $sac_u, $sac_p, $sac_obo, $sac_atom, $sac_package,
	                          $sac_packaging= '', $sac_contenttype = '', $sac_inprogress = false) {


        // Create the package
        $temp = "/Users/stuartlewis/Desktop/MMMM.txt";
        $atom = file_get_contents($sac_atom);
        $xml = "\n";
        $xml .= "--===============SWORDPARTS==\n";
        $xml .= "Content-Type: application/atom+xml\n";
        $xml .= "MIME-Version: 1.0\n";
        $xml .= "Content-Disposition: attachment; name=\"atom\"\n";
        $xml .= "\n";
        $xml .= $atom;
        unset($atom);
        $xml .= "--===============SWORDPARTS==\n";
        $xml .= "Content-Type: " . $sac_contenttype . "\n";
        $xml .= "Content-MD5: " . md5_file($sac_package) . "\n";
        $xml .= "MIME-Version: 1.0\n";
        $xml .= "Content-Disposition: attachment; name=\"payload\"; filename=\"package.zip\"\n";
        $xml .= "Content-Transfer-Encoding: base64\n\n";
        $temp = "/Users/stuartlewis/Desktop/MMMM.txt";
        file_put_contents($temp, $xml);
        $xml = "";
        base64chunk($sac_package, $temp, FILE_APPEND);
        $xml .= "--===============SWORDPARTS==--\n";
        file_put_contents($temp, $xml, FILE_APPEND);

        $my_class_inst = new StreamingClass();
        $my_class_inst->data = fopen($temp, "r");

        $curl_inst = curl_init();

        curl_setopt ($curl_inst, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt ($curl_inst, CURLOPT_LOW_SPEED_LIMIT, 1);
        curl_setopt ($curl_inst, CURLOPT_LOW_SPEED_TIME, 180);
        curl_setopt ($curl_inst, CURLOPT_NOSIGNAL, 1);
        curl_setopt ($curl_inst, CURLOPT_READFUNCTION, array($my_class_inst, "stream_function"));
        curl_setopt ($curl_inst, CURLOPT_URL, $sac_url);
        curl_setopt($curl_inst, CURLOPT_USERPWD, $sac_u . ":" . $sac_p);
        curl_setopt ($curl_inst, CURLOPT_POST, true);

        $header[] = "Packaging: " . $sac_packaging;
        $header[] = "Content-Length: " . filesize("/Users/stuartlewis/Desktop/MMMM.txt");
        $header[] = "Content-Type: multipart/related; boundary=\"===============SWORDPARTS==\"";

        curl_setopt($curl_inst, CURLOPT_HTTPHEADER, $header);
        curl_setopt($curl_inst, CURLOPT_RETURNTRANSFER, 1);

        $sac_resp = curl_exec ($curl_inst);
 	    $sac_status = curl_getinfo($curl_inst, CURLINFO_HTTP_CODE);

        curl_close($curl_inst);

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
		$sac_curl = curl_init();

		if ($this->debug) curl_setopt($sac_curl, CURLOPT_VERBOSE, 1);

		curl_setopt($sac_curl, CURLOPT_URL, $sac_url);
		curl_setopt($sac_curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($sac_curl, CURLOPT_POST, true);
		if(!empty($sac_u) && !empty($sac_p)) {
            curl_setopt($sac_curl, CURLOPT_USERPWD, $sac_u . ":" . $sac_p);
        }
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

    // Complete an incomplete deposit by posting the Imn-Progress header of false to an SE-IRI
    function completeIncompleteDeposit($sac_url, $sac_u, $sac_p, $sac_obo) {
        // Perform the deposit
		$sac_curl = curl_init();

		if ($this->debug) curl_setopt($sac_curl, CURLOPT_VERBOSE, 1);

		curl_setopt($sac_curl, CURLOPT_URL, $sac_url);
		curl_setopt($sac_curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($sac_curl, CURLOPT_POST, true);
		if(!empty($sac_u) && !empty($sac_p)) {
            curl_setopt($sac_curl, CURLOPT_USERPWD, $sac_u . ":" . $sac_p);
        }
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
    function retrieveContent($sac_url, $sac_u, $sac_p, $sac_obo, $sac_accept_packaging = "") {
        // Retrieve the content
        $sac_curl = curl_init();

		curl_setopt($sac_curl, CURLOPT_RETURNTRANSFER, true);
		if ($this->debug) curl_setopt($sac_curl, CURLOPT_VERBOSE, 1);

		curl_setopt($sac_curl, CURLOPT_URL, $sac_url);
		if(!empty($sac_u) && !empty($sac_p)) {
	        curl_setopt($sac_curl, CURLOPT_USERPWD, $sac_u . ":" . $sac_p);
	    }
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

    // Function to delete a container (object)
    function deleteContainer($sac_url, $sac_u, $sac_p, $sac_obo) {
        // Perform the deposit
		$sac_curl = curl_init();

		if ($this->debug) curl_setopt($sac_curl, CURLOPT_VERBOSE, 1);

		curl_setopt($sac_curl, CURLOPT_URL, $sac_url);
		curl_setopt($sac_curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($sac_curl, CURLOPT_CUSTOMREQUEST, "DELETE");
		if(!empty($sac_u) && !empty($sac_p)) {
            curl_setopt($sac_curl, CURLOPT_USERPWD, $sac_u . ":" . $sac_p);
        }
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
}

?>
