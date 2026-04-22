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

    public ?string $name;
    public ?string $default_branch;
    public ?string $ssh_url;

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
