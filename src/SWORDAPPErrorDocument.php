<?php

namespace Swordapp\Client;

class SWORDAPPErrorDocument extends SWORDAPPEntry
{

    /**
     * The error URI
     *
     * @var string
     */
    public $sac_erroruri;

    /**
     * Summary description of error
     *
     * @var string
     */
    public $sac_error_summary;

    /**
     * Verbose description of error
     *
     * @var string
     */
    public $sac_verbose_description;

    /**
     * Construct a new deposit response by passing in the http status code
     *
     * @param int $sac_newstatus
     * @param string $sac_thexml
     */
    function __construct($sac_newstatus, $sac_thexml)
    {
        // Call the super constructor
        parent::__construct($sac_newstatus, $sac_thexml);
    }

    /**
     * Build the error document hierarchy
     *
     * @param \SimpleXMLElement $sac_dr
     * @param array $sac_ns
     */
    function buildhierarchy($sac_dr, $sac_ns)
    {
        // Call the super version
        parent::buildhierarchy($sac_dr, $sac_ns);

        foreach ($sac_dr->attributes() as $key => $value) {
            if ($key == 'href') {
                $this->sac_erroruri = (string)$value;
            }
        }
        // Set error summary & verbose description, if available
        if (isset($sac_dr->children($sac_ns['atom'])->summary)) {
            $this->sac_error_summary = (string)$sac_dr->children($sac_ns['atom'])->summary;
        }
        if (isset($sac_dr->children($sac_ns['sword'])->verboseDescription)) {
            $this->sac_verbose_description = (string)$sac_dr->children($sac_ns['sword'])->verboseDescription;
        }
    }
}
