<?php

require_once('swordappstatemententry.php');
require_once('utils.php');

class SWORDAPPStatement {

    // The XML returned by the deposit
    public $sac_xml;

    // The state of the item
    public $sac_state_href;

    // A description of the state of the item
    public $sac_state_description;

    // An array of entries
    public $sac_entries;

    // Construct a new SWORD statement by passing in the http status code
    function __construct($sac_newstatus, $sac_thexml = '') {
        // Store the xml
        $this->sac_xml = $sac_thexml;

        // Initalise entries
        $this->sac_entries = array();
       
        // Parse the xml if there is some
		if ($sac_thexml != '') {
            $sac_statement = @new SimpleXMLElement($sac_thexml);
            $sac_ns = $sac_statement->getNamespaces(true);
            $sac_state = $sac_statement->children($sac_ns['sword'])->state;
            $sac_state_attributes = $sac_state->attributes();
            $this->sac_state_href = $sac_state_attributes['href'];
            $this->sac_state_description = $sac_state->children($sac_ns['sword'])->stateDescription;

            foreach ($sac_statement->children($sac_ns['atom'])->entry as $sac_entry) {
                $sac_category = $sac_entry->children($sac_ns['atom'])->category;
                $sac_category_attributes = $sac_category->attributes();
                // TODO: Fix this - it currently works against the ss.py, but not against the spec
                $sac_theentry = new SWORDAPPStatementEntry($sac_category_attributes['scheme'],
                                                           $sac_category_attributes['term'],
                                                           $sac_category_attributes['label']);

                $sac_content = $sac_entry->children($sac_ns['atom'])->content;
                $sac_content_attributes = $sac_content->attributes();
                $sac_theentry->addContent($sac_content_attributes['type'],
                                          $sac_content_attributes['src']);

                $sac_theentry->setPackaging($sac_entry->children($sac_ns['sword'])->packaging);

                $sac_theentry->setDepositedOn($sac_entry->children($sac_ns['sword'])->depositedOn);

                $sac_theentry->setDepositedBy($sac_entry->children($sac_ns['sword'])->depositedBy);

                array_push($this->sac_entries, $sac_theentry);
            }
		}
    }

    function toString() {
        print ' - State href: ' . $this->sac_state_href . "\n";
        print ' - State description: ' . $this->sac_state_description . "\n";
        foreach ($this->sac_entries as $sac_entry) {
            $sac_entry->toString();
        }
    }
}