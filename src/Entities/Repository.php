<?php

namespace Nails\Cli\Entities;

final class Repository
{
    private $oRepository;

    // --------------------------------------------------------------------------

    /**
     * Repository constructor.
     *
     * @param \stdClass|null $oRepository
     */
    public function __construct(\stdClass $oRepository = null)
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
