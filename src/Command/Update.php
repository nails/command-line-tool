<?php

namespace Nails\Cli\Command;

final class Update extends Base
{
    /**
     * Configure the command
     */
    protected function configure()
    {
        $this
            ->setName('update')
            ->setDescription('Updates this tool to the latest version')
            ->setHelp('This command will update the Nails Command Line Tool to the latest version');
    }

    // --------------------------------------------------------------------------

    /**
     * Execute the command
     *
     * @return int|null|void
     */
    protected function go()
    {
        return '1.0.1';
        //  @todo (Pablo - 2018-12-13) - Check for updates, update as necessary
    }
}
