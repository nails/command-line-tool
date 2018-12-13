<?php

namespace Nails\Cli\Command;


final class Create extends Base
{
    /**
     * Configure the command
     */
    protected function configure()
    {
        $this
            ->setName('new')
            ->setDescription('Create a new project')
            ->setHelp('This command will create a new Nails project.');
    }

    // --------------------------------------------------------------------------

    /**
     * Execute the command
     *
     * @return int|null|void
     */
    protected function go()
    {
        //  Check directory is empty
        //  Use Docker?
        //  Install
        //  Prepare repository
    }
}
