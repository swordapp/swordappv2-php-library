<?php

namespace Swordapp\Client;

class SWORDAPPLink
{

    /**
     * The 'type' of the link
     *
     * @var string
     */
    public $sac_linktype;

    /**
     * The 'rel' of the link
     *
     * @var string
     */
    public $sac_linkrel;

    /**
     * The 'href' of the link
     *
     * @var string
     */
    public $sac_linkhref;

    /**
     * Construct a new deposit response by passing in the http status code
     *
     * @param string $rel
     * @param string $href
     * @param string $type (optional)
     */
    function __construct($rel, $href, $type = '')
    {
        $this->sac_linkrel = $rel;
        $this->sac_linkhref = $href;
        $this->sac_linktype = $type;
    }
}

