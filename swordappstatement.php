<?php

require_once("utils.php");

class SWORDAPPStatement {

    // The XML returned by the deposit
    public $sac_xml;

    // The state of the item
    public $sac_state_href;

    // A description of the state of the item
    public $sac_state_description;

    // Construct a new deposit response by passing in the http status code
    function __construct($sac_newstatus, $sac_thexml = '') {
        // Store the xml
        $this->sac_xml = $sac_thexml;
       
        // Parse the xml if there is some
		if ($sac_thexml != '') {
            $sac_statement = @new SimpleXMLElement($sac_thexml);
            $sac_ns = $sac_statement->getNamespaces(true);
            $sac_state = $sac_statement->children($sac_ns['sword'])->state;
            $sac_state_attributes = $sac_state->attributes();
            $this->sac_state_href = $sac_state_attributes['href'];
            $this->sac_state_description = $sac_state->children($sac_ns['sword'])->stateDescription;
		}
    }

    function toString() {
        print ' - State href: ' . $this->sac_state_href . "\n";
        print ' - State description: ' . $this->sac_state_description . "\n";
    }
}