<?php

namespace Nails\Cli\Command\Dev;

use Nails\Cli\Command\Base;
use Nails\Cli\Entities\Repository;
use Nails\Cli\Exceptions\Repository\CreateException;
use Nails\Cli\Exceptions\Repository\FetchException;
use Nails\Cli\Exceptions\Repository\UpdateException;
use Nails\Cli\Exceptions\RepositoryException;
use Nails\Cli\Helper\Curl;
use Nails\Cli\Helper\Directory;

final class Pull extends Base
{
    /**
     * Configure the command
     */
    protected function configure()
    {
        $this
            ->setName('dev:pull')
            ->setDescription('Pull a copy of all active Nails repositories')
            ->setHelp('This command will clone all active Nails repositories from GitHub to the active directory');
    }

    // --------------------------------------------------------------------------

    /**
     * Execute the command
     *
     * @return int|null|void
     */
    protected function go()
    {
        $this->banner('Updating Nails Repositories');

        try {

            $aNames        = [];
            $aRepositories = $this->fetchRepositories();

            foreach ($aRepositories as $oRepository) {
                $aNames[] = $oRepository->full_name;
            }

            $aLengths   = array_map('strlen', $aNames);
            $iMaxLength = max($aLengths) + 2;

            $this->oOutput->writeln('');

            foreach ($aRepositories as $oRepository) {

                $this->oOutput->write('- <comment>' . str_pad($oRepository->full_name, $iMaxLength, ' ') . '</comment>');

                try {

                    if ($this->repositoryExists($oRepository) && $oRepository->archived) {

                        $this->repositoryDelete($oRepository);
                        $this->oOutput->writeln('<error>deleted</error>');

                    } elseif ($this->repositoryExists($oRepository) && !$oRepository->archived) {

                        $this->repositoryUpdate($oRepository);
                        $this->oOutput->writeln('<comment>updated</comment>');

                    } elseif (!$oRepository->archived) {

                        $this->repositoryCreate($oRepository);
                        $this->oOutput->writeln('<comment>created</comment>');

                    } else {
                        $this->oOutput->writeln('<comment>Archived</comment>');
                    }

                } catch (RepositoryException $e) {
                    $this->oOutput->writeln('<error>' . $e->getMessage() . '</error>');
                }
            }

            $this->oOutput->writeln('');
            $this->oOutput->writeln('Finished processing repositories');

        } catch (FetchException $e) {
            $this->oOutput->writeln('<error>' . $e->getMessage() . '</error>');
        } catch (\RuntimeException $e) {
            $this->error([$e->getMessage()]);
        }

        $this->oOutput->writeln('');
    }

    // --------------------------------------------------------------------------

    /**
     * Fetches the all the repositories from GitHub
     *
     * @return array
     */
    private function fetchRepositories()
    {
        //  @todo (Pablo - 2018-12-13) - Support authenticated requests
        $this->oOutput->write('Fetching repositories from GitHub... ');

        $iPage         = 1;
        $aRepositories = [];

        while (($sResponse = Curl::get('https://api.github.com/orgs/nails/repos?page=' . $iPage)) !== '[]') {
            $aResponse = json_decode($sResponse);
            if (is_array($aResponse)) {
                foreach ($aResponse as $oRepository) {
                    $aRepositories[] = $oRepository;
                }
            } else {
                throw new FetchException('Failed to retrieve repositories from GitHub (rate limited)');
            }
            $iPage++;
        }

        sort($aRepositories);

        $aOut = [];
        foreach ($aRepositories as $oRepository) {
            $aOut[] = new Repository($oRepository);
        }

        $this->oOutput->writeln('received ' . count($aOut) . ' repositories');

        return $aOut;
    }

    // --------------------------------------------------------------------------

    /**
     * Checks to see if a repository exists
     *
     * @param Repository $oRepository The repository to create
     *
     * @return bool
     */
    private function repositoryExists(Repository $oRepository)
    {
        //  @todo (Pablo - 2018-12-13) - USE ->name once finished
        $sPath = getcwd() . Directory::normalize('/' . $oRepository->full_name);
        return is_dir($sPath);
    }

    // --------------------------------------------------------------------------

    /**
     * Deletes an existing repository
     *
     * @param Repository $oRepository The repository to delete
     */
    private function repositoryDelete(Repository $oRepository)
    {
        $sPath     = $this->getRepositoryPath($oRepository);
        $aCommands = [
            'rm -rf "' . $sPath . '"',
        ];
        $sCommand  = implode(' && ', $aCommands);

        exec($sCommand, $sOutput, $iReturn);

        if ($iReturn !== 0) {
            throw new UpdateException('Failed to create repository');
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Updates an existing repository
     *
     * @param Repository $oRepository The repository to update
     */
    private function repositoryUpdate(Repository $oRepository)
    {
        $sPath     = $this->getRepositoryPath($oRepository);
        $aCommands = [
            'cd "' . $sPath . '"',
            'git checkout develop 2>&1',
            'git pull origin master 2>&1',
        ];
        $sCommand  = implode(' && ', $aCommands);

        exec($sCommand, $sOutput, $iReturn);

        if ($iReturn !== 0) {
            throw new UpdateException('Failed to create repository');
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Creates a new repository
     *
     * @param Repository $oRepository The repository to create
     */
    private function repositoryCreate(Repository $oRepository)
    {
        $sPath     = $this->getRepositoryPath($oRepository);
        $aCommands = [
            'mkdir -p "' . $sPath . '"',
            'cd "' . $sPath . '"',
            'git clone ' . $oRepository->ssh_url . ' . 2>&1',
        ];
        $sCommand  = implode(' && ', $aCommands);

        exec($sCommand, $sOutput, $iReturn);

        if ($iReturn !== 0) {
            throw new CreateException('Failed to create repository: ' . trim($sOutput));
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the path for where to install the repository
     *
     * @param Repository $oRepository The repository being installed
     *
     * @return string
     */
    private function getRepositoryPath(Repository $oRepository)
    {
        //  @todo (Pablo - 2018-12-13) - USE ->name once finished
        return getcwd() . Directory::normalize('/' . $oRepository->full_name);
    }
}
