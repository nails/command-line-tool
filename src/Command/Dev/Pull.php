<?php

namespace Nails\Cli\Command\Dev;

use Nails\Cli\Command\Base;
use Nails\Cli\Entities\Repository;
use Nails\Cli\Exceptions\Directory\DoesNotExistException;
use Nails\Cli\Exceptions\Repository\FetchException;
use Nails\Cli\Helper\Curl;
use Nails\Cli\Helper\Directory;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\ConsoleSectionOutput;
use Symfony\Component\Process\Process;

final class Pull extends Base
{
    /**
     * Default number of concurrent processes
     */
    const DEFAULT_CONCURRENCY = 4;

    // --------------------------------------------------------------------------

    /**
     * Configure the command
     */
    protected function configure(): void
    {
        $this
            ->setName('dev:pull')
            ->setDescription('Pull a copy of all active Nails repositories')
            ->setHelp('This command will clone all active Nails repositories from GitHub to the active directory')
            ->addOption(
                'branch',
                'b',
                InputOption::VALUE_OPTIONAL,
                'The branch to pull'
            )
            ->addOption(
                'dir',
                'd',
                InputOption::VALUE_OPTIONAL,
                'Where to install, defaults to current working directory'
            )
            ->addOption(
                'concurrency',
                'c',
                InputOption::VALUE_OPTIONAL,
                'The number of concurrent repositories to process',
                self::DEFAULT_CONCURRENCY
            );
    }

    // --------------------------------------------------------------------------

    /**
     * Execute the command
     *
     * @return int
     */
    protected function go(): int
    {
        $this->banner('Updating Nails Repositories');

        try {

            $this->validateDirectory();

            $aRepositories = $this->fetchRepositories();
            $aNames        = array_map(fn(Repository $r) => (string) $r->full_name, $aRepositories);
            $iMaxLength    = !empty($aNames) ? max(array_map('strlen', $aNames)) + 2 : 40;

            $this->oOutput->writeln('');

            $this->processRepositories($aRepositories, $iMaxLength);

            $this->oOutput->writeln('');
            $this->oOutput->writeln('Finished processing repositories');

        } catch (FetchException $e) {
            $this->oOutput->writeln('<error>' . $e->getMessage() . '</error>');
        } catch (\RuntimeException $e) {
            $this->error([$e->getMessage()]);
        }

        $this->oOutput->writeln('');

        return static::EXIT_CODE_SUCCESS;
    }

    // --------------------------------------------------------------------------

    /**
     * Processes repositories concurrently using a worker pool
     *
     * @param Repository[] $aRepositories The repositories to process
     * @param int          $iMaxLength    The maximum repository name length for alignment
     */
    private function processRepositories(array $aRepositories, int $iMaxLength): void
    {
        $iConcurrency = (int) $this->oInput->getOption('concurrency');
        $iConcurrency = max(1, $iConcurrency > 0 ? $iConcurrency : self::DEFAULT_CONCURRENCY);

        $aQueue = [];
        foreach ($aRepositories as $oRepository) {
            $bExists = $this->repositoryExists($oRepository);

            if ($bExists) {
                $sAction = $oRepository->archived ? 'delete' : 'update';
            } elseif (!$oRepository->archived) {
                $sAction = 'create';
            } else {
                continue;
            }

            $aQueue[] = [
                'repo'   => $oRepository,
                'action' => $sAction,
            ];
        }

        $bSupportsSections = ($this->oOutput instanceof ConsoleOutputInterface && $this->oOutput->isDecorated());
        $oCompletedSection = null;
        /** @var ConsoleSectionOutput[] $aWorkerSections */
        $aWorkerSections   = [];

        if ($bSupportsSections) {
            /** @var ConsoleOutputInterface $oOutput */
            $oOutput           = $this->oOutput;
            $oCompletedSection = $oOutput->section();
            for ($i = 0; $i < $iConcurrency; $i++) {
                $aWorkerSections[$i] = $oOutput->section();
            }
        }

        $aRunning = []; // Keyed by slot ID: ['repo' => Repository, 'action' => string, 'process' => Process]

        while (!empty($aQueue) || !empty($aRunning)) {

            // Fill available slots
            for ($iSlot = 0; $iSlot < $iConcurrency; $iSlot++) {
                if (!isset($aRunning[$iSlot]) && !empty($aQueue)) {
                    $aTask = array_shift($aQueue);
                    /** @var Repository $oRepo */
                    $oRepo   = $aTask['repo'];
                    $sAction = $aTask['action'];

                    $oProcess = $this->createRepositoryProcess($oRepo, $sAction);
                    $oProcess->start();

                    if (isset($aWorkerSections[$iSlot])) {
                        $aWorkerSections[$iSlot]->overwrite(
                            '- <comment>' . str_pad((string) $oRepo->full_name, $iMaxLength, ' ') . '</comment><comment>running...</comment>'
                        );
                    }

                    $aRunning[$iSlot] = [
                        'repo'    => $oRepo,
                        'action'  => $sAction,
                        'process' => $oProcess,
                    ];
                }
            }

            // Poll running processes
            foreach ($aRunning as $iSlot => $aRunningTask) {
                /** @var Process $oProcess */
                $oProcess = $aRunningTask['process'];
                /** @var Repository $oRepo */
                $oRepo    = $aRunningTask['repo'];
                $sAction  = $aRunningTask['action'];

                if (!$oProcess->isRunning()) {
                    if ($oProcess->isSuccessful()) {
                        $sStatus = match ($sAction) {
                            'delete' => '<error>deleted</error>',
                            'update' => '<info>updated</info>',
                            'create' => '<info>created</info>',
                        };
                    } else {
                        $sErrorOutput = trim($oProcess->getErrorOutput() ?: $oProcess->getOutput());
                        $sStatus      = match ($sAction) {
                            'delete' => '<error>Failed to delete repository: ' . $sErrorOutput . '</error>',
                            'update' => '<error>Failed to update repository: ' . $sErrorOutput . '</error>',
                            'create' => '<error>Failed to create repository: ' . $sErrorOutput . '</error>',
                        };
                    }

                    $sLine = '- <comment>' . str_pad((string) $oRepo->full_name, $iMaxLength, ' ') . '</comment>' . $sStatus;

                    if ($oCompletedSection instanceof ConsoleSectionOutput) {
                        if (isset($aWorkerSections[$iSlot])) {
                            $aWorkerSections[$iSlot]->clear();
                        }
                        $oCompletedSection->writeln($sLine);
                    } else {
                        $this->oOutput->writeln($sLine);
                    }

                    unset($aRunning[$iSlot]);
                }
            }

            if (!empty($aRunning)) {
                usleep(25000); // 25ms
            }
        }

        foreach ($aWorkerSections as $oSection) {
            $oSection->clear();
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Creates a Process instance for repository action
     *
     * @param Repository $oRepository The repository
     * @param string     $sAction     The action to perform ('delete', 'update', 'create')
     *
     * @return Process
     */
    private function createRepositoryProcess(Repository $oRepository, string $sAction): Process
    {
        $sPath   = $this->getRepositoryPath($oRepository);
        $sBranch = $this->getBranch($oRepository) ?: 'master';

        $sBranchCheck = sprintf(
            '(git show-ref --verify --quiet refs/heads/%s || git show-ref --verify --quiet refs/remotes/origin/%s) || (echo %s 1>&2 && exit 1)',
            escapeshellarg($sBranch),
            escapeshellarg($sBranch),
            escapeshellarg("branch {$sBranch} does not exist")
        );

        if ($sAction === 'delete') {
            $oProcess = Process::fromShellCommandline('rm -rf ' . escapeshellarg($sPath));
        } elseif ($sAction === 'update') {
            $sCmd = implode(' && ', [
                'cd ' . escapeshellarg($sPath),
                'git fetch 2>&1',
                $sBranchCheck,
                'git checkout ' . escapeshellarg($sBranch) . ' 2>&1',
                '(git show-ref --verify --quiet refs/remotes/origin/' . escapeshellarg($sBranch) . ' && git pull origin ' . escapeshellarg($sBranch) . ' 2>&1 || true)',
            ]);
            $oProcess = Process::fromShellCommandline($sCmd);
        } else {
            $sCmd = implode(' && ', [
                'mkdir -p ' . escapeshellarg($sPath),
                'cd ' . escapeshellarg($sPath),
                'git clone ' . escapeshellarg($oRepository->ssh_url ?? '') . ' . 2>&1',
                $sBranchCheck,
                'git checkout ' . escapeshellarg($sBranch) . ' 2>&1',
            ]);
            $oProcess = Process::fromShellCommandline($sCmd);
        }

        $oProcess->setTimeout(null);

        return $oProcess;
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

        usort($aRepositories, fn($a, $b) => $a->name <=> $b->name);

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
        $sPath = $this->getRepositoryPath($oRepository);
        return is_dir($sPath);
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the branch to checkout/pull for the repository
     *
     * @param Repository $oRepository The repository
     *
     * @return string|null
     */
    private function getBranch(Repository $oRepository): ?string
    {
        return $this->oInput->getOption('branch') ?: $oRepository->default_branch;
    }

    // --------------------------------------------------------------------------

    /**
     * Validates that the target directory exists
     *
     * @return $this
     * @throws DoesNotExistException
     */
    private function validateDirectory(): self
    {
        $sDir = $this->getDirectory();
        if (!Directory::exists($sDir)) {
            throw new DoesNotExistException('"' . $sDir . '" does not exist');
        }

        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the base directory for repositories
     *
     * @return string
     */
    private function getDirectory(): string
    {
        $sDir = $this->oInput->getOption('dir') ?: getcwd();
        return Directory::resolve($sDir);
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the path for where to install the repository
     *
     * @param Repository $oRepository The repository being installed
     *
     * @return string
     */
    private function getRepositoryPath(Repository $oRepository): string
    {
        $sDir = $this->getDirectory();

        return rtrim($sDir, '/\\') . Directory::normalize('/' . $oRepository->name);
    }
}
