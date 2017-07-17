<?php

namespace Swordapp\Client;

class PackagerMetsSwap
{
    /**
     * The location of the files (without final directory)
     *
     * @var string
     */
    public $sac_root_in;

    /**
     * The directory to zip up in the $sac_root_in directory
     *
     * @var string
     */
    public $sac_dir_in;

    /**
     * The location to write the package out to
     *
     * @var string
     */
    public $sac_root_out;

    /**
     * The filename to save the package as
     *
     * @var string
     */
    public $sac_file_out;

    /**
     * The name of the metadata file
     *
     * @var string
     */
    public $sac_metadata_filename = 'mets.xml';

    /**
     * The type (e.g. ScholarlyWork)
     *
     * @var string
     */
    public $sac_type;

    /**
     * The title of the item
     *
     * @var string
     */
    public $sac_title;

    /**
     * The abstract of the item
     *
     * @var string
     */
    public $sac_abstract;

    /**
     * Creators
     *
     * @var array
     */
    public $sac_creators;

    /**
     * Subjects
     *
     * @var array
     */
    public $sac_subjects;

    /**
     * Identifier
     *
     * @var string
     */
    public $sac_identifier;

    /**
     * Date made available
     *
     * @var
     */
    public $sac_dateavailable;

    /**
     * Status
     *
     * @var string
     */
    public $sac_statusstatement;

    /**
     * Copyright holder
     *
     * @var string
     */
    public $sac_copyrightholder;

    /**
     * Custodian
     *
     * @var string
     */
    public $sac_custodian;

    /**
     * Bibliographic citation
     *
     * @var string
     */
    public $sac_citation;

    /**
     * Language
     *
     * @var string
     */
    public $sac_language;

    /**
     * File name
     *
     * @var array
     */
    public $sac_files;

    /**
     * MIME type
     *
     * @var array
     */
    public $sac_mimetypes;

    /**
     * Provenances
     *
     * @var array
     */
    public $sac_provenances;

    /**
     * Rights
     *
     * @var array
     */
    public $sac_rights;

    /**
     * Publisher
     *
     * @var string
     */
    public $sac_publisher;

    /**
     * Number of files added
     *
     * @var int
     */
    public $sac_filecount;


    /**
     * @param string $sac_rootin
     * @param string $sac_dirin
     * @param string $sac_rootout
     * @param string $sac_fileout
     */
    public function __construct($sac_rootin, $sac_dirin, $sac_rootout, $sac_fileout)
    {
        // Store the values
        $this->sac_root_in = $sac_rootin;
        $this->sac_dir_in = $sac_dirin;
        $this->sac_root_out = $sac_rootout;
        $this->sac_file_out = $sac_fileout;
        $this->sac_creators = array();
        $this->sac_subjects = array();
        $this->sac_files = array();
        $this->sac_mimetypes = array();
        $this->sac_provenances = array();
        $this->sac_rights = array();
        $this->sac_filecount = 0;
    }

    /**
     * @param string $sac_thetype
     */
    public function setType($sac_thetype)
    {
        $this->sac_type = $sac_thetype;
    }

    /**
     * @param string $sac_thetitle
     */
    public function setTitle($sac_thetitle)
    {
        $this->sac_title = $this->clean($sac_thetitle);
    }

    /**
     * @param string $sac_thetitle
     */
    public function setAbstract($sac_thetitle)
    {
        $this->sac_abstract = $this->clean($sac_thetitle);
    }

    /**
     * @param string $sac_creator
     */
    public function addCreator($sac_creator)
    {
        array_push($this->sac_creators, $this->clean($sac_creator));
    }

    /**
     * @param string $sac_subject
     */
    public function addSubject($sac_subject)
    {
        array_push($this->sac_subjects, $this->clean($sac_subject));
    }

    /**
     * @param string $sac_provenance
     */
    public function addProvenance($sac_provenance)
    {
        array_push($this->sac_provenances, $this->clean($sac_provenance));
    }

    /**
     * @param string $sac_right
     */
    public function addRights($sac_right)
    {
        array_push($this->sac_rights, $this->clean($sac_right));
    }

    /**
     * @param string $sac_theidentifier
     */
    public function setIdentifier($sac_theidentifier)
    {
        $this->sac_identifier = $sac_theidentifier;
    }

    /**
     * @param string $sac_thestatus
     */
    public function setStatusStatement($sac_thestatus)
    {
        $this->sac_statusstatement = $sac_thestatus;
    }

    /**
     * @param string $sac_thecopyrightholder
     */
    public function setCopyrightHolder($sac_thecopyrightholder)
    {
        $this->sac_copyrightholder = $this->clean($sac_thecopyrightholder);
    }

    /**
     * @param string $sac_thecustodian
     */
    public function setCustodian($sac_thecustodian)
    {
        $this->sac_custodian = $this->clean($sac_thecustodian);
    }

    /**
     * @param string $sac_thecitation
     */
    public function setCitation($sac_thecitation)
    {
        $this->sac_citation = $this->clean($sac_thecitation);
    }

    /**
     * @param string $sac_thelanguage
     */
    public function setLanguage($sac_thelanguage)
    {
        $this->sac_language = $this->clean($sac_thelanguage);
    }

    /**
     * @param string $sac_thedta
     */
    public function setDateAvailable($sac_thedta)
    {
        $this->sac_dateavailable = $sac_thedta;
    }

    /**
     * @param string $sac_thepublisher
     */
    public function setPublisher($sac_thepublisher)
    {
        $this->sac_publisher = $sac_thepublisher;
    }

    /**
     * @param string $sac_thefile
     * @param string $sac_themimetype
     */
    public function addFile($sac_thefile, $sac_themimetype)
    {
        array_push($this->sac_files, $sac_thefile);
        array_push($this->sac_mimetypes, $sac_themimetype);
        $this->sac_filecount++;
    }

    /**
     * @param string $sac_theelement
     * @param string $sac_thevalue
     */
    public function addMetadata($sac_theelement, $sac_thevalue)
    {
        switch ($sac_theelement) {
            case "abstract":
                $this->setAbstract($sac_thevalue);
                break;
            case "available":
                $this->setDateAvailable($sac_thevalue);
                break;
            case "bibliographicCitation":
                $this->setCitation($sac_thevalue);
                break;
            case "creator":
                $this->addCreator($sac_thevalue);
                break;
            case "identifier":
                $this->setIdentifier($sac_thevalue);
                break;
            case "publisher":
                $this->setPublisher($sac_thevalue);
                break;
            case "title":
                $this->setTitle($sac_thevalue);
                break;
        }
    }

    /**
     * @throws \Exception
     */
    public function create()
    {
        // Write the metadata (mets) file
        $fh = @fopen($this->sac_root_in . '/' . $this->sac_dir_in . '/' . $this->sac_metadata_filename, 'w');
        if (!$fh) {
            throw new \Exception(
                "Error writing metadata file (" .
                $this->sac_root_in . '/' . $this->sac_dir_in . '/' . $this->sac_metadata_filename . ")"
            );
        }
        $this->writeHeader($fh);
        $this->writeDmdSec($fh);
        $this->writeFileGrp($fh);
        $this->writeStructMap($fh);
        $this->writeFooter($fh);
        fclose($fh);

        // Create the zipped package (force an overwrite if it already exists)
        $zip = new \ZipArchive();
        $zip->open($this->sac_root_out . '/' . $this->sac_file_out, \ZIPARCHIVE::CREATE | \ZIPARCHIVE::OVERWRITE);
        $zip->addFile(
            $this->sac_root_in . '/' . $this->sac_dir_in . '/mets.xml',
            'mets.xml'
        );
        for ($i = 0; $i < $this->sac_filecount; $i++) {
            $zip->addFile(
                $this->sac_root_in . '/' . $this->sac_dir_in . '/' . $this->sac_files[$i],
                $this->sac_files[$i]
            );
        }
        $zip->close();
    }

    /**
     * @param resource $fh
     */
    public function writeheader($fh)
    {
        fwrite($fh, "<?xml version=\"1.0\" encoding=\"utf-8\" standalone=\"no\" ?" . ">\n");
        fwrite(
            $fh,
            "<mets ID=\"sort-mets_mets\" OBJID=\"sword-mets\" LABEL=\"DSpace SWORD Item\" PROFILE=\"DSpace METS SIP Profile 1.0\" xmlns=\"http://www.loc.gov/METS/\" xmlns:xlink=\"http://www.w3.org/1999/xlink\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xsi:schemaLocation=\"http://www.loc.gov/METS/ http://www.loc.gov/standards/mets/mets.xsd\">\n" // @codingStandardsIgnoreLine
        );
        fwrite($fh, "\t<metsHdr CREATEDATE=\"2008-09-04T00:00:00\">\n");
        fwrite($fh, "\t\t<agent ROLE=\"CUSTODIAN\" TYPE=\"ORGANIZATION\">\n");
        if (isset($this->sac_custodian)) {
            fwrite($fh, "\t\t\t<name>$this->sac_custodian</name>\n");
        } else {
            fwrite($fh, "\t\t\t<name>Unknown</name>\n");
        }
            fwrite($fh, "\t\t</agent>\n");
            fwrite($fh, "\t</metsHdr>\n");
    }

    /**
     * @param resource $fh
     */
    public function writeDmdSec($fh)
    {
        fwrite($fh, "<dmdSec ID=\"sword-mets-dmd-1\" GROUPID=\"sword-mets-dmd-1_group-1\">\n");
        fwrite($fh, "<mdWrap LABEL=\"SWAP Metadata\" MDTYPE=\"OTHER\" OTHERMDTYPE=\"EPDCX\" MIMETYPE=\"text/xml\">\n");
        fwrite($fh, "<xmlData>\n");
        fwrite(
            $fh,
            "<epdcx:descriptionSet xmlns:epdcx=\"http://purl.org/eprint/epdcx/2006-11-16/\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xsi:schemaLocation=\"http://purl.org/eprint/epdcx/2006-11-16/ http://purl.org/eprint/epdcx/xsd/2006-11-16/epdcx.xsd\">\n" // @codingStandardsIgnoreLine
        );
        fwrite($fh, "<epdcx:description epdcx:resourceId=\"sword-mets-epdcx-1\">\n");

        if (isset($this->sac_type)) {
            $this->statementVesURIValueURI(
                $fh,
                "http://purl.org/dc/elements/1.1/type",
                "http://purl.org/eprint/terms/Type",
                $this->sac_type
            );
        }

        if (isset($this->sac_title)) {
            $this->statement(
                $fh,
                "http://purl.org/dc/elements/1.1/title",
                $this->valueString($this->sac_title)
            );
        }

        if (isset($this->sac_abstract)) {
            $this->statement(
                $fh,
                "http://purl.org/dc/terms/abstract",
                $this->valueString($this->sac_abstract)
            );
        }

        foreach ($this->sac_creators as $sac_creator) {
            $this->statement(
                $fh,
                "http://purl.org/dc/elements/1.1/creator",
                $this->valueString($sac_creator)
            );
        }

        foreach ($this->sac_subjects as $sac_subject) {
            $this->statement(
                $fh,
                "http://purl.org/dc/elements/1.1/subject",
                $this->valueString($sac_subject)
            );
        }

        foreach ($this->sac_provenances as $sac_provenance) {
            $this->statement(
                $fh,
                "http://purl.org/dc/terms/provenance",
                $this->valueString($sac_provenance)
            );
        }

        foreach ($this->sac_rights as $sac_right) {
            $this->statement(
                $fh,
                "http://purl.org/dc/terms/rights",
                $this->valueString($sac_right)
            );
        }

        if (isset($this->sac_identifier)) {
            $this->statement(
                $fh,
                "http://purl.org/dc/elements/1.1/identifier",
                $this->valueString($this->sac_identifier)
            );
        }

        if (isset($this->sac_publisher)) {
            $this->statement(
                $fh,
                "http://purl.org/dc/elements/1.1/publisher",
                $this->valueString($this->sac_publisher)
            );
        }

        fwrite(
            $fh,
            "<epdcx:statement epdcx:propertyURI=\"http://purl.org/eprint/terms/isExpressedAs\" " .
            "epdcx:valueRef=\"sword-mets-expr-1\" />\n"
        );

        fwrite($fh, "</epdcx:description>\n");

        fwrite($fh, "<epdcx:description epdcx:resourceId=\"sword-mets-expr-1\">\n");

        $this->statementValueURI(
            $fh,
            "http://purl.org/dc/elements/1.1/type",
            "http://purl.org/eprint/entityType/Expression"
        );

        if (isset($this->sac_language)) {
            $this->statementVesURI(
                $fh,
                "http://purl.org/dc/elements/1.1/language",
                "http://purl.org/dc/terms/RFC3066",
                $this->valueString($this->sac_language)
            );
        }

        $this->statementVesURIValueURI(
            $fh,
            "http://purl.org/dc/elements/1.1/type",
            "http://purl.org/eprint/terms/Type",
            "http://purl.org/eprint/entityType/Expression"
        );

        if (isset($this->sac_dateavailable)) {
            $this->statement(
                $fh,
                "http://purl.org/dc/terms/available",
                $this->valueStringSesURI(
                    "http://purl.org/dc/terms/W3CDTF",
                    $this->sac_dateavailable
                )
            );
        }

        if (isset($this->sac_statusstatement)) {
            $this->statementVesURIValueURI(
                $fh,
                "http://purl.org/eprint/terms/Status",
                "http://purl.org/eprint/terms/Status",
                $this->sac_statusstatement
            );
        }

        if (isset($this->sac_copyrightholder)) {
            $this->statement(
                $fh,
                "http://purl.org/eprint/terms/copyrightHolder",
                $this->valueString($this->sac_copyrightholder)
            );
        }

        if (isset($this->sac_citation)) {
            $this->statement(
                $fh,
                "http://purl.org/eprint/terms/bibliographicCitation",
                $this->valueString($this->sac_citation)
            );
        }

        fwrite($fh, "</epdcx:description>\n");

        fwrite($fh, "</epdcx:descriptionSet>\n");
        fwrite($fh, "</xmlData>\n");
        fwrite($fh, "</mdWrap>\n");
        fwrite($fh, "</dmdSec>\n");
    }

    /**
     * @param resource $fh
     */
    public function writeFileGrp($fh)
    {
        fwrite($fh, "\t<fileSec>\n");
        fwrite($fh, "\t\t<fileGrp ID=\"sword-mets-fgrp-1\" USE=\"CONTENT\">\n");
        for ($i = 0; $i < $this->sac_filecount; $i++) {
            fwrite(
                $fh,
                "\t\t\t<file GROUPID=\"sword-mets-fgid-0\" ID=\"sword-mets-file-" . $i . "\" " .
                "MIMETYPE=\"" . $this->sac_mimetypes[$i] . "\">\n"
            );
            fwrite(
                $fh,
                "\t\t\t\t<FLocat LOCTYPE=\"URL\" xlink:href=\"" . $this->clean($this->sac_files[$i]) . "\" />\n"
            );
            fwrite($fh, "\t\t\t</file>\n");
        }
        fwrite($fh, "\t\t</fileGrp>\n");
        fwrite($fh, "\t</fileSec>\n");
    }

    /**
     * @param resource $fh
     */
    public function writeStructMap($fh)
    {
        fwrite($fh, "\t<structMap ID=\"sword-mets-struct-1\" LABEL=\"structure\" TYPE=\"LOGICAL\">\n");
        fwrite($fh, "\t\t<div ID=\"sword-mets-div-1\" DMDID=\"sword-mets-dmd-1\" TYPE=\"SWORD Object\">\n");
        fwrite($fh, "\t\t\t<div ID=\"sword-mets-div-2\" TYPE=\"File\">\n");
        for ($i = 0; $i < $this->sac_filecount; $i++) {
            fwrite($fh, "\t\t\t\t<fptr FILEID=\"sword-mets-file-" . $i . "\" />\n");
        }
        fwrite($fh, "\t\t\t</div>\n");
        fwrite($fh, "\t\t</div>\n");
        fwrite($fh, "\t</structMap>\n");
    }

    /**
     * @param resource $fh
     */
    public function writeFooter($fh)
    {
        fwrite($fh, "</mets>\n");
    }

    /**
     * @param string $value
     * @return string
     */
    public function valueString($value)
    {
        return "<epdcx:valueString>" .
            $value .
            "</epdcx:valueString>\n";
    }

    /**
     * @param string $sesURI
     * @param string $value
     * @return string
     */
    public function valueStringSesURI($sesURI, $value)
    {
        return "<epdcx:valueString epdcx:sesURI=\"" . $sesURI . "\">" .
            $value .
            "</epdcx:valueString>\n";
    }

    /**
     * @param resource $fh
     * @param string $propertyURI
     * @param string $value
     */
    public function statement($fh, $propertyURI, $value)
    {
        fwrite(
            $fh,
            "<epdcx:statement epdcx:propertyURI=\"" . $propertyURI . "\">\n" .
            $value .
            "</epdcx:statement>\n"
        );
    }

    /**
     * @param resource $fh
     * @param string $propertyURI
     * @param string $value
     */
    public function statementValueURI($fh, $propertyURI, $value)
    {
        fwrite(
            $fh,
            "<epdcx:statement epdcx:propertyURI=\"" . $propertyURI . "\" " .
            "epdcx:valueURI=\"" . $value . "\" />\n"
        );
    }

    /**
     * @param resource $fh
     * @param string $propertyURI
     * @param string $vesURI
     * @param string $value
     */
    public function statementVesURI($fh, $propertyURI, $vesURI, $value)
    {
        fwrite(
            $fh,
            "<epdcx:statement epdcx:propertyURI=\"" . $propertyURI . "\" " .
            "epdcx:vesURI=\"" . $vesURI . "\">\n" .
            $value .
            "</epdcx:statement>\n"
        );
    }

    /**
     * @param resource $fh
     * @param string $propertyURI
     * @param string $vesURI
     * @param string $value
     */
    public function statementVesURIValueURI($fh, $propertyURI, $vesURI, $value)
    {
        fwrite(
            $fh,
            "<epdcx:statement epdcx:propertyURI=\"" . $propertyURI . "\" " .
            "epdcx:vesURI=\"" . $vesURI . "\" " .
            "epdcx:valueURI=\"" . $value . "\" />\n"
        );
    }

    /**
     * @param string $data
     * @return string
     */
    public function clean($data)
    {
        return str_replace('&#039;', '&apos;', htmlspecialchars($data, ENT_QUOTES));
    }
}
