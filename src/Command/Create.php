<?php

namespace Nails\Cli\Command;


use Nails\Cli\Exceptions\Directory\FailedToCreateException;
use Nails\Cli\Exceptions\Directory\NotEmptyException;
use Nails\Cli\Exceptions\Zip\CannotOpenException;
use Nails\Cli\Helper\Directory;
use Nails\Cli\Helper\System;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Finder\Finder;

final class Create extends Base
{
    /**
     * The URL of the Docker skeleton
     *
     * @var string
     */
    const DOCKER_SKELETON = 'https://github.com/nails/skeleton-docker-lamp/archive/master.zip';


    /**
     * The URL of the Docker skeleton
     *
     * @var string
     */
    const APP_SKELETON = 'https://github.com/nails/skeleton-app/archive/master.zip';

    // --------------------------------------------------------------------------

    /**
     * Configure the command
     */
    protected function configure()
    {
        $this
            ->setName('new')
            ->setDescription('Create a new project')
            ->setHelp('This command will create a new Nails project.')
            ->addOption('dir', 'd', InputOption::VALUE_OPTIONAL, 'Where to install, defaults to current working directory')
            ->addOption('no-docker', null, InputOption::VALUE_NONE, 'Do not install the Docker environment');
    }

    // --------------------------------------------------------------------------

    /**
     * Execute the command
     *
     * @return int|null|void
     */
    protected function go()
    {
        $this->banner('Install a new Nails project');

        //  Check directory is empty
        $sDir = $this->oInput->getOption('dir') ?: getcwd();
        $sDir = Directory::resolve($sDir);

        if (!Directory::isEmpty($sDir)) {
            throw new NotEmptyException('"' . $sDir . '" is not empty');
        }

        //  Create working directory
        if (!Directory::exists($sDir)) {
            $this->oOutput->write('Creating directory <comment>' . $sDir . '</comment>... ');
            if (!mkdir($sDir)) {
                throw new FailedToCreateException('Could not create directory "' . $sDir . '"');
            }
            $this->oOutput->writeln('<info>done</info>');
        }

        //  Use Docker?
        if ($this->oInput->getOption('no-docker')) {
            $this->installWithoutDocker($sDir);
        } else {
            $this->installWithDocker($sDir);
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Installs the skeleton app to the specified directory
     *
     * @param string $sDir The directory to install to
     */
    private function installWithoutDocker($sDir)
    {
        $this->install($sDir, static::APP_SKELETON, 'skeleton-app-master');

        $this->oOutput->writeln('');
        $this->oOutput->writeln('Project has been configured at <comment>' . $sDir . '</comment>');
        $this->oOutput->writeln('Run <comment>nails install</comment> to complete installation');
        $this->oOutput->writeln('');
    }

    // --------------------------------------------------------------------------

    /**
     * Install the Docker skeleton
     *
     * @param string $sDir The directory to install to
     */
    private function installWithDocker($sDir)
    {
        $this->install($sDir, static::DOCKER_SKELETON, 'skeleton-docker-lamp-master');

        $this->oOutput->writeln('');
        $this->oOutput->writeln('Project has been configured at <comment>' . $sDir . '</comment>');
        $this->oOutput->writeln('Run <comment>make up && make build</comment> to build containers and complete installation');
        $this->oOutput->writeln('');
    }

    // --------------------------------------------------------------------------

    /**
     * Downloads and extracts a zip archive to a particular location
     *
     * @param string $sDir        The target directory
     * @param string $sArchiveUrl The URL of the archive
     * @param string $sRepoName   The name of the repo
     */
    private function install($sDir, $sArchiveUrl, $sRepoName)
    {
        $this->oOutput->write('Downloading archive... ');
        $sZipPath = $sDir . 'app.zip';
        file_put_contents($sZipPath, file_get_contents($sArchiveUrl));
        $this->oOutput->writeln('<info>done</info>');

        //  Extract
        $oZip = new \ZipArchive();
        $this->oOutput->write('Extracting archive... ');
        if ($oZip->open($sZipPath) === true) {

            $oZip->extractTo($sDir);
            $oZip->close();

            System::exec('mv ' . $sDir . $sRepoName . '/* ' . rtrim($sDir, '/') . '');
            System::exec('mv ' . $sDir . $sRepoName . '/.[a-z]* ' . rtrim($sDir, '/') . '');

            $this->oOutput->writeln('<info>done</info>');

        } else {
            $this->oOutput->writeln('<error>failed</error>');
            throw new CannotOpenException('Failed to extract archive');
        }

        //  Make all the .sh files executable
        $this->oOutput->write('Setting file permissions... ');
        $oFinder = new Finder();
        $oFinder->name('*.sh');
        foreach ($oFinder->in($sDir) as $oFile) {
            System::exec('chmod +x "' . $oFile->getPath() . '/' . $oFile->getFilename() . '"');
        }
        $this->oOutput->writeln('<info>done</info>');

        //  Tidy up
        $this->oOutput->write('Tidying up... ');
        unlink($sZipPath);
        rmdir($sDir . $sRepoName);
        $this->oOutput->writeln('<info>done</info>');
    }
}