<?php

namespace Swordapp\Client;

require_once __DIR__ . '/utils/namespace.php';

use function Swordapp\Client\Utils\sac_clean;

class Collection
{
    /**
     * The title of the collection
     *
     * @var string
     */
    public $sac_colltitle;

    /**
     * The URL of the collection (where you can deposit to)
     *
     * @var string
     */
    public $sac_href;

    /**
     * The types of content accepted
     *
     * @var array
     */
    public $sac_accept;

    /**
     * The  alternative types of content accepted
     *
     * @var array
     */
    public $sac_acceptalternative;

    /**
     * The accepted packaging formats
     *
     * @var array
     */
    public $sac_acceptpackaging;

    /**
     * The collection policy
     *
     * @var string
     */
    public $sac_collpolicy;

    /**
     * The colelction abstract (dcterms)
     *
     * @var string
     */
    public $sac_abstract;

    /**
     * Whether mediation is allowed or not
     *
     * @var bool
     */
    public $sac_mediation;

    /**
     * A nested service document
     *
     * @var string
     */
    public $sac_service;

    /**
     * Construct a new collection by passing in a title
     *
     * @param string $sac_newcolltitle
     */
    function __construct($sac_newcolltitle)
    {
        // Store the title
        $this->sac_colltitle = sac_clean($sac_newcolltitle);

        // Create the accepts arrays
        $this->sac_accept = array();
        $this->sac_acceptalternative = array();
        $this->sac_acceptpackaging = array();
    }

    /**
     * Add a new supported packaging type
     *
     * @param mixed $ap
     */
    function addAcceptPackaging($ap)
    {
        $format = (string)$ap[0];
        $q = (string)$ap[0]['q'];
        if (empty($q)) {
            $q = "1.0";
        }
        $this->sac_acceptpackaging[$format] = $q;
    }
}