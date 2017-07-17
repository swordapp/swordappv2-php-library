<?php

namespace Swordapp\Client;

class SWORDAPPResponse extends SWORDAPPEntry
{

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

}
