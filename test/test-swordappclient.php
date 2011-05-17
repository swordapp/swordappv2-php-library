<?php
    // Test the V2 PHP client implementation using the Simple SWORD Server (SSS)

	// The URL of the service document
	$testurl = "http://localhost/sss/sd-uri";
	
	// The user (if required)
	$testuser = "sword";
	
	// The password of the user (if required)
	$testpw = "sword";
	
	// The on-behalf-of user (if required)
	//$testobo = "user@swordapp.com";

	// The URL of the example deposit collection
	$testdepositurl = "http://localhost/sss/col-uri/da9b9feb-4266-446a-8847-46f6c30b2ff0";

	// The test atom entry to deposit
	$testatom = "test-files/atom_multipart/atom";

	// The test file to deposit
	$testfile = "test-files/atom_multipart_package";

	// The content type of the test file
	$testcontenttype = "application/zip";

	// The packaging format of the test fifle
	$testformat = "http://purl.org/net/sword/package/SimpleZip";
	
	require("../swordappclient.php");
    $testsac = new SWORDAPPClient();

	if (true) {
		print "About to request servicedocument from " . $testurl . "\n";
		if (empty($testuser)) {
            print "As: anonymous\n";
        } else {
            print "As: " . $testuser . "\n";
        }
		$testsdr = $testsac->servicedocument($testurl, $testuser, $testpw, $testobo);
		print "Received HTTP status code: " . $testsdr->sac_status . " (" . $testsdr->sac_statusmessage . ")\n";

		if ($testsdr->sac_status == 200) {
            $testsdr->toString();
        }
	}

	print "\n\n";
	
	if (true) {
		print "About to deposit file (" . $testfile . ") to " . $testdepositurl . "\n";
		if (empty($testuser)) {
            print "As: anonymous\n";
        } else {
            print "As: " . $testuser . "\n";
        }
		$testdr = $testsac->depositMultipart($testdepositurl, $testuser, $testpw, $testobo, $testfile, $testformat, false);
		print "Received HTTP status code: " . $testdr->sac_status . " (" . $testdr->sac_statusmessage . ")\n";
		
		if (($testdr->sac_status >= 200) || ($testdr->sac_status < 300)) {
            $testdr->toString();
        }

        $edit_iri = $testdr->sac_edit_iri;
    }

    print "\n\n";

    /**
    if (false) {
        print "About to complete the deposit at " . $complete_url . "\n";
        if (empty($testuser)) {
            print "As: anonymous\n";
        } else {
            print "As: " . $testuser . "\n";
        }
        $testdr = $testsac->completeIncompleteDeposit($testdepositurl, $testuser, $testpw, $testobo);
        print "Received HTTP status code: " . $testdr->sac_status .
              " (" . $testdr->sac_statusmessage . ")\n";

        if (($testdr->sac_status >= 200) || ($testdr->sac_status < 300)) {
            $testdr->toString();
        }
    }

    print "\n\n";
    */

    if (false) {
        print "About to delete container at " . $edit_iri . "\n";
        if (empty($testuser)) {
                print "As: anonymous\n";
            } else {
                print "As: " . $testuser . "\n";
            }
            try {
                $deleteresponse = $testsac->deleteContainer($edit_iri, $testuser, $testpw, $testobo);
                print " - Container successfully deleted, HTTP code 204\n";
            } catch (Exception $e) {
                echo $e->getMessage();
            }
    }

    if (false) {
        print "About to deposit atom entry (" . $testatom . ") to " . $testdepositurl . "\n";
        if (empty($testuser)) {
            print "As: anonymous\n";
        } else {
            print "As: " . $testuser . "\n";
        }
        $testdr = $testsac->depositAtomEntry($testdepositurl, $testuser, $testpw, $testobo, $testatom, true);
        print "Received HTTP status code: " . $testdr->sac_status .
              " (" . $testdr->sac_statusmessage . ")\n";

        if (($testdr->sac_status >= 200) || ($testdr->sac_status < 300)) {
            $testdr->toString();
        }
    }

	print "\n\n";

    if (false) {
        print "About to retrieve content from " . $edit_iri . "\n";
        if (empty($testuser)) {
            print "As: anonymous\n";
        } else {
            print "As: " . $testuser . "\n";
        }
		$testdr = $testsac->retrieveContent($edit_iri, $testuser, $testpw, $testobo, "http://purl.org/net/sword/package/SimpleZip");
		print "Received HTTP status code: " . $testsdr->sac_status . " (" . $testsdr->sac_statusmessage . ")\n";
		if ($testdr->sac_status == 200) {
            $testdr->toString();
        }
    }

?>
