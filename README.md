Welcome to the SWORD v2 PHP client library!
-------------------------------------------

The SWORDAPP v2 PHP client library is a PHP library designed to assist with the creation of 
[SWORD deposit tools](http://swordapp.org/about/). This client library allows PHP code to easily make use of SWORD 
functions:

 + **GETting** a Service Document
 + **POSTing** a package or file into a repository
 + **PUTting** an updated file into a repository
 + **DELETEing** a file or item from a repository

The library was originally written by Stuart Lewis (stuart@stuartlewis.com) as part of the JISC funded SWORD2 project.

This is the Pressbooks fork that library. The latest version of can be installed using Composer

    composer require pressbooks/swordapp  

Limited support for this forked version of the library is available on GitHub:

 + https://github.com/pressbooks/swordappv2-php-library

Licence
-------
The library is licenced with the New BSD Licence. See the file LICENCE in the distribution directory.


SWORD Compatibility
-------------------
This version of the library is compatible with SWORD version 2.0


Prerequisites
-------------
This library requires:

 + PHP version 5.6+
   + CURL extension
   + SimpleXML extension
   + ZIP extension (for the packager only)


How to use the library
----------------------
The easiest way to understand the library is to look in the test/examples/ directory which contain integration tests 
that exercises all the  functions and variables of the library.

The main methods to use are from swordappclient.php:

 + `servicedocument($sac_url, $sac_u, $sac_p, $sac_obo)`
 + `deposit($sac_url, $sac_u, $sac_p, $sac_obo, $sac_fname, $sac_packaging= '', $sac_contenttype = '', $sac_inprogress = false)`
 + `depositMultipart($sac_url, $sac_u, $sac_p, $sac_obo, $sac_package, $sac_inprogress = false)`
 + `depositAtomEntry($sac_url, $sac_u, $sac_p, $sac_obo, $sac_fname, $sac_inprogress = false)`
 + `completeIncompleteDeposit($sac_url, $sac_u, $sac_p, $sac_obo)`
 + `retrieveContent($sac_url, $sac_u, $sac_p, $sac_obo, $sac_accept_packaging = ''')`
 + `retrieveDepositReceipt($sac_url, $sac_u, $sac_p, $sac_obo, $sac_accept_packaging = ''')`
 + `replaceFileContent($sac_url, $sac_u, $sac_p, $sac_obo, $sac_fname, $sac_packaging= '', $sac_contenttype = '', $sac_metadata_relevant = false)`
 + `replaceMetadata($sac_url, $sac_u, $sac_p, $sac_obo, $sac_fname, $sac_inprogress = false)`
 + `replaceMetadataAndFile($sac_url, $sac_u, $sac_p, $sac_obo, $sac_package, $sac_inprogress = false)`
 + `addExtraFileToMediaResource($sac_url, $sac_u, $sac_p, $sac_obo, $sac_fname, $sac_contenttype = '', $sac_metadata_relevant = false)`
 + `addExtraPackage($sac_url, $sac_u, $sac_p, $sac_obo, $sac_fname, $sac_packaging = '', $sac_contenttype, $sac_inprogress = false)`
 + `addExtraAtomEntry($sac_url, $sac_u, $sac_p, $sac_obo, $sac_fname, $sac_inprogress = false)`
 + `addExtraMultipartPackage($sac_url, $sac_u, $sac_p, $sac_obo, $sac_package, $sac_inprogress = false)`
 + `deleteContainer($sac_url, $sac_u, $sac_p, $sac_obo)`
 + `deleteResourceContent($sac_url, $sac_u, $sac_p, $sac_obo)`
 + `retrieveAtomStatement($sac_url, $sac_u, $sac_p, $sac_obo)`
 + `retrieveOAIOREStatement($sac_url, $sac_u, $sac_p, $sac_obo)`

The functions return a `\Swordapp\Client\SWORDAPPServiceDocument`, a `\Swordapp\Client\SWORDAPPEntry`, a 
`\Swordapp\Client\SWORDAPPResponse` or a `\Swordapp\Client\SWORDAPPErrorDocument` object as appropriate. These classes 
can then be interrogated (e.g. `$servicedocument->sac_workspaces`).

The main function parameters are:

 + `$sac_url`: The URL to interact with (e.g. service document URL, deposit URL, etc)
 + `$sac_u`: The username
 + `$sac_p`: The password
 + `$sac_obo`: The on-behalf-of user name (optional, can be left empty)
 + `$sac_fname`: Filename to deposit
 + `$sac_packaging`: THe packaging format of the package being deposited
 + `$sac_inprogress`: Whether or not the deposit should be considered 'in-progress'

The SWORD v2 specification can be seen at:

 + http://swordapp.org/sword-v2/sword-v2-specifications/


Packages
--------
Deposits can be made using packages.  By default, all SWORD v2 interfaces should accept Atom Multipart packages.  These 
can be generated using the `PackagerAtomMultipart.php` class. Some repositories will accept METS+SWAP packages. 
These can be generated using the `PackagerMetsSwap.php` class.


Differences in Pressbooks' version of SWORD Client v2
-----------------------------------------------------

 + PSR2 coding standards with namespaces
 + Can be installed using [Composer](https://getcomposer.org/)

Changes
-------
0.2.0 (July 2017)
 + Refactor
 + PSR2 coding standards with namespaces
 + Composer compatible

0.1 (18th October 2011)
 - First release, forked from version 1 of the library
   (https://github.com/stuartlewis/swordapp-php-library/)
