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
	$testdepositurl = "http://localhost/sss/col-uri/e6b163cb-a2bd-4dd6-b783-e241a2e67068";

	// The test atom entry to deposit
	$testatom = "test-files/atom_multipart/atom";

	// The test file to deposit
	$testfile = "test-files/atom_multipart_package.zip";

	// The content type of the test file
	$testcontenttype = "application/zip";

	// The packaging format of the test fifle
	$testformat = "http://purl.org/net/sword/package/default";
	
	require("../swordappclient.php");
	$testsac = new SWORDAPPClient();

	if (true) {
		print "About to request servicedocument from " . $testurl . "\n";
		if (empty($testuser)) { print "As: anonymous\n"; }
		else { print "As: " . $testuser . "\n"; }
		$testsdr = $testsac->servicedocument(
			       $testurl, $testuser, $testpw, $testobo);
		print "Received HTTP status code: " . $testsdr->sac_status . 
		      " (" . $testsdr->sac_statusmessage . ")\n";

		if ($testsdr->sac_status == 200) {
			print " - Version: " . $testsdr->sac_version . "\n";
			print " - Supports Verbose: " . $testsdr->sac_verbose . "\n";
			print " - Supports NoOp: " . $testsdr->sac_noop . "\n";
			print " - Maximum uplaod size: ";
			if (!empty($testsdr->sac_maxuploadsize)) {
				print $testsdr->sac_maxuploadsize . " kB\n";
			} else {
				print "undefined\n";
			}
		
			$workspaces = $testsdr->sac_workspaces;
			foreach ($testsdr->sac_workspaces as $workspace) {
				$wstitle = $workspace->sac_workspacetitle;
				echo "   - Workspace: ".$wstitle."\n";
				$collections = $workspace->sac_collections;
				foreach ($collections as $collection) {
					$ctitle = $collection->sac_colltitle;
					echo "     - Collection: " . $ctitle . " (" . $collection->sac_href . ")\n";
					if (count($collection->sac_accept) > 0) {
	        	        	        foreach ($collection->sac_accept as $accept) {
		        	        	        echo "        - Accepts: " . $accept . "\n";
		                	        }		
					}
					if (count($collection->sac_acceptpackaging) > 0) {
	        	        	        foreach ($collection->sac_acceptpackaging as $acceptpackaging => $q) {
		        	        	        echo "        - Accepted packaging format: " . 
							     $acceptpackaging . " (q=" . $q . ")\n";
		                	        }		
					}
					if (!empty($collection->sac_collpolicy)) {
						echo "        - Collection Policy: " . $collection->sac_collpolicy . "\n";
					}
					echo "        - Collection abstract: " . $collection->sac_abstract . "\n";
					$mediation = "false";
					if ($collection->sac_mediation == true) { $mediation = "true"; }
					echo "        - Mediation: " . $mediation . "\n";
					if (!empty($collection->sac_service)) {
						echo "        - Service document: " . $collection->sac_service . "\n";
					}
				}	
			}
		}
	}

	print "\n\n";
	
	if (true) {
		print "About to deposit file (" . $testfile . ") to " . $testdepositurl . "\n";
		if (empty($testuser)) { print "As: anonymous\n"; }
		else { print "As: " . $testuser . "\n"; }
		$testdr = $testsac->depositMultipart($testdepositurl, $testuser, $testpw, $testobo, $testatom, $testfile, $testformat, $testcontenttype);
		print "Received HTTP status code: " . $testdr->sac_status . 
		      " (" . $testdr->sac_statusmessage . ")\n";
		
		if (($testdr->sac_status >= 200) || ($testdr->sac_status < 300)) {
			print " - ID: " . $testdr->sac_id . "\n";
			print " - Title: " . $testdr->sac_title . "\n";
			print " - Content: " . $testdr->sac_content_src . 
			      " (" . $testdr->sac_content_type . ")\n";
			foreach ($testdr->sac_authors as $author) {
				print "  - Author: " . $author . "\n";
			}
			foreach ($testdr->sac_contributors as $contributor) {
				print "  - Contributor: " . $contributor . "\n";
			}
			foreach ($testdr->sac_links as $link) {
				print "  - Link: " . $link . "\n";
			}
			print " - Summary: " . $testdr->sac_summary . "\n";
			print " - Updated: " . $testdr->sac_updated . "\n";
            print " - Rights: " . $testdr->sac_rights . "\n";
            print " - Treatment: " . $testdr->sac_treatment . "\n";
            print " - Verbose description: " . $testdr->sac_verbose_treatment . "\n";
			print " - Packaging: " . $testdr->sac_packaging . "\n";
			print " - Generator: " . $testdr->sac_generator . 
			      " (" . $testdr->sac_generator_uri . ")\n";
			print " - User agent: " . $testdr->sac_useragent . "\n";
			if (!empty($testdr->sac_noOp)) { print " - noOp: " . $testdr->sac_noOp . "\n"; }

            foreach ($testdr->sac_dcterms as $dcterm => $dcvalues) {
                print ' - Dublin Core Metadata: ' . $dcterm . "\n";
                foreach ($dcvalues as $dcvalue) {
                    print '    - ' . $dcvalue . "\n";
                }
            }
		}
	}

	print "\n\n";

?>
