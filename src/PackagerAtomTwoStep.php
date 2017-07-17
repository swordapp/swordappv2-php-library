<?php

namespace Swordapp\Client;

class PackagerAtomTwoStep
{
    /**
     * The location of the files (without final directory)
     *
     * @var string
     */
    private $sac_root_in;

    /**
     * The location to write the package out to
     *
     * @var string
     */
    private $sac_root_out;

    /**
     * The filename to save the package as
     *
     * @var string
     */
    private $sac_file_out;

    /**
     * File names
     *
     * @var array
     */
    private $sac_files;

    /**
     * Number of files added
     *
     * @var int
     */
    private $sac_filecount;

    /**
     * dcterms metadata
     *
     * @var array
     */
    private $sac_entry_dctermsFields;

    /**
     * dcterms metadata
     *
     * @var array
     */
    private $sac_entry_dctermsValues;

    /**
     * dcterms metadata
     *
     * @var array
     */
    private $sac_entry_dctermsAttributes;

    /**
     * The entry title
     *
     * @var string
     */
    private $sac_entry_title;

    /**
     * The entry id
     *
     * @var string
     */
    private $sac_entry_id;

    /**
     * The entry updated date / time stamp
     *
     * @var string
     */
    private $sac_entry_updated;

    /**
     * The entry author names
     *
     * @var array
     */
    private $sac_entry_authors;

    /**
     * The entry summary text
     *
     * @var string
     */
    private $sac_entry_summary;

    /**
     * @param string $sac_rootin
     * @param string $sac_dirin
     * @param string $sac_rootout
     * @param string $sac_fileout
     */
    function __construct($sac_rootin, $sac_dirin, $sac_rootout, $sac_fileout)
    {
        // Store the values
        $this->sac_root_in = $sac_rootin;
        $this->sac_dir_in = $sac_dirin;
        $this->sac_root_out = $sac_rootout;
        $this->sac_file_out = $sac_fileout;

        $this->sac_files = array();
        $this->sac_mimetypes = array();
        $this->sac_filecount = 0;

        $this->sac_entry_dctermsFields = array();
        $this->sac_entry_dctermsValues = array();
        $this->sac_entry_dctermsAttributes = array();

        $this->sac_entry_authors = array();
    }

    /**
     * @param string $sac_thetitle
     */
    function setTitle($sac_thetitle)
    {
        $this->sac_entry_title = $this->clean($sac_thetitle);
    }

    /**
     * @param string $sac_theID
     */
    function setIdentifier($sac_theID)
    {
        $this->sac_entry_id = $this->clean($sac_theID);
    }

    /**
     * @param string $sac_theUpdated
     */
    function setUpdated($sac_theUpdated)
    {
        $this->sac_entry_updated = $this->clean($sac_theUpdated);
    }

    /**
     * @param string $sac_theauthor
     */
    function addEntryAuthor($sac_theauthor)
    {
        array_push($this->sac_entry_authors, $this->clean($sac_theauthor));
    }

    /**
     * @param string $sac_theSummary
     */
    function setSummary($sac_theSummary)
    {
        $this->sac_entry_summary = $this->clean($sac_theSummary);
    }

    /**
     * @param string $sac_theElement
     * @param string $sac_theValue
     * @param array $sac_theAttributes (optional)
     */
    function addMetadata($sac_theElement, $sac_theValue, $sac_theAttributes = array())
    {
        array_push($this->sac_entry_dctermsFields, $this->clean($sac_theElement));
        array_push($this->sac_entry_dctermsValues, $this->clean($sac_theValue));
        $sac_cleanAttributes = array();
        foreach ($sac_theAttributes as $attrName => $attrValue) {
            $sac_cleanAttributes[$this->clean($attrName)] = $this->clean($attrValue);
        }
        array_push($this->sac_entry_dctermsAttributes, $sac_cleanAttributes);
    }

    /**
     * @param string $sac_thefile
     */
    function addFile($sac_thefile)
    {
        array_push($this->sac_files, $sac_thefile);
        $this->sac_filecount++;
    }

    /**
     * @return array
     */
    function getFiles()
    {
        return $this->sac_files;
    }

    /**
     * @throws \Exception
     */
    function create()
    {
        // Write the atom entry manifest
        $sac_atom = $this->sac_root_in . '/' . $this->sac_dir_in . '/atom';
        $fh = @fopen($sac_atom, 'w');
        if (!$fh) {
            throw new \Exception(
                "Error writing atom entry manifest (" .
                $this->sac_root_in . '/' . $this->sac_dir_in . '/atom)'
            );
        }

        // Write the atom entry header
        fwrite($fh, "<?xml version=\"1.0\"?>\n");
        fwrite($fh, "<entry xmlns=\"http://www.w3.org/2005/Atom\" xmlns:dcterms=\"http://purl.org/dc/terms/\">\n");
        if (!empty($this->sac_entry_title)) { fwrite($fh, "\t<title>" . $this->sac_entry_title . "</title>\n");
        }
        if (!empty($this->sac_entry_id)) { fwrite($fh, "\t<id>" . $this->sac_entry_id . "</id>\n");
        }
        if (!empty($this->sac_entry_updated)) { fwrite($fh, "\t<updated>" . $this->sac_entry_updated . "</updated>\n");
        }
        foreach ($this->sac_entry_authors as $sac_author) {
            fwrite($fh, "\t<author><name>" . $sac_author . "</name></author>\n");
        }
        if (!empty($this->sac_entry_summary)) { fwrite($fh, "\t<summary>" . $this->sac_entry_summary . "</summary>\n");
        }

        // Write the dcterms metadata
        for ($i = 0; $i < count($this->sac_entry_dctermsFields); $i++) {
            $dcElement = "\t<dcterms:" . $this->sac_entry_dctermsFields[$i];
            if (!empty($this->sac_entry_dctermsAttributes[$i])) {
                foreach ($this->sac_entry_dctermsAttributes[$i] as $attrName => $attrValue) {
                    $dcElement .= " $attrName=\"$attrValue\"";
                }
            }
            $dcElement .= ">" . $this->sac_entry_dctermsValues[$i] . "</dcterms:" . $this->sac_entry_dctermsFields[$i] . ">\n";
            fwrite($fh, $dcElement);
        }

        // Close the file
        fwrite($fh, "</entry>\n");
        fclose($fh);
    }

    /**
     * @param string $data
     * @return string
     */
    function clean($data)
    {
        return str_replace('&#039;', '&apos;', htmlspecialchars($data, ENT_QUOTES));
    }
}
