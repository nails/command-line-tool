<?php

namespace Nails\Cli\Entities;

final class Repository
{
    /**
     * The repository object
     *
     * @var \stdClass
     */
    public readonly \stdClass $oRepository;

    public readonly ?string $name;
    public readonly ?string $full_name;
    public readonly ?string $default_branch;
    public readonly ?string $ssh_url;
    public readonly ?bool $archived;

    // --------------------------------------------------------------------------

    /**
     * Repository constructor.
     *
     * @param \stdClass $oRepository
     */
    public function __construct(\stdClass $oRepository)
    {
        $this->oRepository    = $oRepository;
        $this->name           = $oRepository->name ?? null;
        $this->full_name      = $oRepository->full_name ?? null;
        $this->default_branch = $oRepository->default_branch ?? null;
        $this->ssh_url        = $oRepository->ssh_url ?? null;
        $this->archived       = $oRepository->archived ?? null;
    }
}
