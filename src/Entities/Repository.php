<?php

namespace Nails\Cli\Entities;

final class Repository
{
    /**
     * The repository object
     *
     * @var \stdClass
     */
    private $oRepository;

    // --------------------------------------------------------------------------

    /**
     * Repository constructor.
     *
     * @param \stdClass $oRepository
     */
    public function __construct(\stdClass $oRepository)
    {
        $this->oRepository = $oRepository;
    }

    // --------------------------------------------------------------------------

    /**
     * @param string $oProperty The property to get
     *
     * @return mixed
     */
    public function __get($oProperty)
    {
        return $this->oRepository->{$oProperty};
    }
}
