<?php

namespace Swordapp\Client;

class SWORDAppStatementEntry
{

    /**
     * The scheme of the entry
     *
     * @var string
     */
    public $sac_scheme;

    /**
     * The term of the entry
     *
     * @var string
     */
    public $sac_term;

    /**
     * The label for the entry
     *
     * @var string
     */
    public $sac_label;

    /**
     * The content type
     *
     * @var string
     */
    public $sac_content_type;

    /**
     * // The content source
     *
     * @var string
     */
    public $sac_content_source;

    /**
     * The packaging format used
     *
     * @var string
     */
    public $sac_packaging;

    /**
     * When it was deposited
     *
     * @var string
     */
    public $sac_deposited_on;

    /**
     * Who deposited it
     *
     * @var string
     */
    public $sac_deposited_by;

    /**
     *
     * @param string $sac_scheme
     * @param string $sac_term
     * @param string $sac_label
     */
    public function __construct($sac_scheme, $sac_term, $sac_label)
    {
        $this->sac_scheme = $sac_scheme;
        $this->sac_term = $sac_term;
        $this->sac_label = $sac_label;
    }

    /**
     * Set the content type and source
     *
     * @param string $sac_type
     * @param string $sac_src
     */
    public function addContent($sac_type, $sac_src)
    {
        $this->sac_content_type = $sac_type;
        $this->sac_content_source = $sac_src;
    }

    /**
     * Set the packaging
     *
     * @param string $sac_packaging
     */
    public function setPackaging($sac_packaging)
    {
        $this->sac_packaging = $sac_packaging;
    }

    /**
     * Set the deposited date
     *
     * @param string $sac_deposited_on
     */
    public function setDepositedOn($sac_deposited_on)
    {
        $this->sac_deposited_on = $sac_deposited_on;
    }

    /**
     * Set the deposited by
     *
     * @param string $sac_deposited_by
     */
    public function setDepositedBy($sac_deposited_by)
    {
        $this->sac_deposited_by = $sac_deposited_by;
    }

    /**
     * Print out a representation of the statement
     */
    public function toString()
    {
        print "  - Entry:\n";
        print "   - Scheme: " . $this->sac_scheme . "\n";
        print "   - Term: " . $this->sac_term . "\n";
        print "   - Label: " . $this->sac_label . "\n";
        print "   - Content: Type=" . $this->sac_content_type . " Source=" . $this->sac_content_source . "\n";
        print "   - Packaging: " . $this->sac_packaging . "\n";
        print "   - Deposited On: " . $this->sac_deposited_on . "\n";
        print "   - Deposited By: " . $this->sac_deposited_by . "\n";
    }
}
