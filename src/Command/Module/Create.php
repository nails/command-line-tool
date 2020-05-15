<?php

namespace Nails\Cli\Command\Module;

use Nails\Cli\Command\Base;
use Nails\Cli\Exceptions\Directory\FailedToCreateException;
use Nails\Cli\Exceptions\Zip\CannotOpenException;
use Nails\Cli\Helper\Directory;
use Nails\Cli\Helper\System;
use Symfony\Component\Console\Input\InputOption;
use ZipArchive;

final class Create extends Base
{
    /**
     * The URL of the skeleton archive
     *
     * @var string
     */
    const MODULE_SKELETON = 'https://github.com/nails/skeleton-module/archive/master.zip';

    // --------------------------------------------------------------------------

    /**
     * The directory in which to create the module
     *
     * @var string
     */
    protected $sDir;

    /**
     * The module's name
     *
     * @var string
     */
    protected $sName;

    /**
     * The module's URL
     *
     * @var string
     */
    protected $sUrl;

    /**
     * The module's description
     *
     * @var string
     */
    protected $sDescription;

    /**
     * The module's namespace
     *
     * @var string
     */
    protected $sNamespace;

    // --------------------------------------------------------------------------

    /**
     * Configure the command
     */
    protected function configure()
    {
        $this->sDir = getcwd();

        // --------------------------------------------------------------------------

        $this
            ->setName('new:module')
            ->setDescription('Create a new Nails module')
            ->setHelp('This command will create a new Nails module.')
            ->addOption(
                'directory',
                '',
                InputOption::VALUE_OPTIONAL,
                'Where to install, defaults to current working directory'
            )
            ->addOption(
                'name',
                '',
                InputOption::VALUE_OPTIONAL,
                'The module\'s name; in the following format: {vendor}/{name}'
            )
            ->addOption(
                'url',
                '',
                InputOption::VALUE_OPTIONAL,
                'The module\'s URL'
            )
            ->addOption(
                'description',
                '',
                InputOption::VALUE_OPTIONAL,
                'The module\'s description'
            )
            ->addOption(
                'namespace',
                '',
                InputOption::VALUE_OPTIONAL,
                'The module\'s namespace'
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
        $this->banner('Create a new Nails module');
        $this->setVarDirectory();
        $this->setVarName();
        $this->setVarNamespace();
        $this->setVarUrl();
        $this->setVarDescription();

        // --------------------------------------------------------------------------

        $this->oOutput->writeln('Does this look ok?');

        $this->oOutput->writeln('');
        $this->oOutput->writeln('Dir:         <comment>' . $this->sDir . '</comment>');
        $this->oOutput->writeln('Name:        <comment>' . $this->sName . '</comment>');
        $this->oOutput->writeln('Namespace:   <comment>' . $this->sNamespace . '</comment>');
        $this->oOutput->writeln('Url:         <comment>' . $this->sUrl . '</comment>');
        $this->oOutput->writeln('Description: <comment>' . $this->sDescription . '</comment>');
        $this->oOutput->writeln('');

        // --------------------------------------------------------------------------

        if ($this->confirm('Continue')) {

            //  Create working directory
            if (!Directory::exists($this->sDir)) {
                $this->oOutput->write('Creating directory <comment>' . $this->sDir . '</comment>... ');
                if (!mkdir($this->sDir)) {
                    throw new FailedToCreateException('Could not create directory "' . $this->sDir . '"');
                }
                $this->oOutput->writeln('<info>done</info>');
            }

            $this->oOutput->write('Downloading archive... ');
            $sZipPath = $this->sDir . 'module.zip';
            file_put_contents($sZipPath, file_get_contents(static::MODULE_SKELETON));
            $this->oOutput->writeln('<info>done</info>');

            // --------------------------------------------------------------------------

            //  Extract
            $oZip = new ZipArchive();
            $this->oOutput->write('Extracting archive... ');
            if ($oZip->open($sZipPath) === true) {

                $oZip->extractTo($this->sDir);
                $oZip->close();

                System::exec('mv ' . $this->sDir . 'skeleton-module-master/* ' . rtrim($this->sDir, '/') . '');
                System::exec('mv ' . $this->sDir . 'skeleton-module-master/.[a-z]* ' . rtrim($this->sDir, '/') . '');

                $this->oOutput->writeln('<info>done</info>');

            } else {
                $this->oOutput->writeln('<error>failed</error>');
                throw new CannotOpenException('Failed to extract archive');
            }

            // --------------------------------------------------------------------------

            //  Rewrite composer.json
            $this->oOutput->write('Updating composer.json... ');
            $oComposer = json_decode(file_get_contents($this->sDir . 'composer.json'));

            list($sVendor, $sName) = explode('/', $this->sName);

            $oComposer->name                        = $this->sName;
            $oComposer->homepage                    = $this->sUrl;
            $oComposer->description                 = $this->sDescription;
            $oComposer->extra->nails->moduleName    = $sName;
            $oComposer->extra->nails->namespace     = $this->sNamespace . '\\';
            $oComposer->autoload->{"psr-4"}         = (object) [$this->sNamespace . '\\' => 'src/'];
            $oComposer->{"autoload-dev"}->{"psr-4"} = (object) [$this->sNamespace . '\\Tests\\' => 'src/'];

            file_put_contents($this->sDir . 'composer.json', json_encode($oComposer, JSON_PRETTY_PRINT));

            $this->oOutput->writeln('<info>done</info>');

            // --------------------------------------------------------------------------

            //  Tidy up
            $this->oOutput->write('Tidying up... ');
            unlink($sZipPath);
            rmdir($this->sDir . 'skeleton-module-master');
            $this->oOutput->writeln('<info>done</info>');

            // --------------------------------------------------------------------------

            $this->oOutput->writeln('');
            $this->oOutput->writeln('Module has been created at <comment>' . $this->sDir . '</comment>');
            $this->oOutput->writeln('');
        }

        return static::EXIT_CODE_SUCCESS;
    }

    // --------------------------------------------------------------------------

    /**
     * Sets the directory
     */
    protected function setVarDirectory(): void
    {
        $this->sDir = $this->oInput->getOption('directory');
        if ($this->sDir) {
            if (!$this->validateDirectory($this->sDir)) {
                $this->sDir = $this->askForDirectory();
            }
        } else {
            $this->sDir = $this->askForDirectory();
        }

        $this->sDir = Directory::resolve($this->sDir);
    }

    // --------------------------------------------------------------------------

    /**
     * Requests the directory from the user
     *
     * @param string|null $sDefault The default directory
     *
     * @return mixed
     */
    protected function askForDirectory(string $sDefault = null)
    {
        return $this->ask(
            'Directory',
            $sDefault,
            [$this, 'validateDirectory']
        );
    }

    // --------------------------------------------------------------------------

    /**
     * Validates the directory
     *
     * @param string|null $sDir Validates the directory
     *
     * @return bool
     */
    protected function validateDirectory(string $sDir = null)
    {
        $sDir = Directory::resolve($sDir);
        if (!Directory::isEmpty($sDir)) {
            $this->error(['"' . $sDir . '" is not empty']);
            return false;
        }
        return true;
    }

    // --------------------------------------------------------------------------

    /**
     * Sets the name
     */
    protected function setVarName(): void
    {
        $this->sName = trim($this->oInput->getOption('name'));
        if ($this->sName) {
            if (!$this->validateName($this->sName)) {
                $this->sName = trim($this->askForName());
            }
        } else {
            $this->sName = trim($this->askForName());
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Requests the name from the user
     *
     * @param string|null $sDefault The default name
     *
     * @return mixed
     */
    protected function askForName(string $sDefault = null)
    {
        return $this->ask(
            'Name ({vendor}/{name})',
            $sDefault,
            [$this, 'validateName']
        );
    }

    // --------------------------------------------------------------------------

    /**
     * Validates the name
     *
     * @param string|null $sName Validates the name
     *
     * @return bool|void
     */
    protected function validateName(string $sName = null)
    {
        if (!preg_match('/^[a-z\-0-9]+\/[a-z\-0-9]+$/', $sName)) {
            $this->error(['"' . $sName . '" is not in the format [a-z\-0-9]+/[a-z\-0-9]+']);
            return false;
        }
        return true;
    }

    // --------------------------------------------------------------------------

    /**
     * Sets the namespace
     */
    protected function setVarNamespace(): void
    {
        $this->sNamespace = trim($this->oInput->getOption('namespace'));
        if ($this->sNamespace) {
            if (!$this->validateNamespace($this->sNamespace)) {
                $this->sNamespace = trim($this->askForNamespace());
            }
        } else {
            $this->sNamespace = trim($this->askForNamespace());
        }

        $this->sNamespace = rtrim($this->sNamespace, '\\');
        $this->sNamespace = str_replace('\\\\', '\\', $this->sNamespace);
    }

    // --------------------------------------------------------------------------

    /**
     * Requests the namespace from the user
     *
     * @param string|null $sDefault The default Namespace
     *
     * @return mixed
     */
    protected function askForNamespace(string $sDefault = null)
    {
        return $this->ask(
            'Namespace',
            $sDefault,
            [$this, 'validateNamespace']
        );
    }

    // --------------------------------------------------------------------------

    /**
     * Validates the namespace
     *
     * @param string|null $sNamespace Validates the namespace
     *
     * @return bool
     */
    protected function validateNamespace(string $sNamespace = null)
    {
        if (!preg_match('/^[a-zA-Z0-9\\\_]+$/', $sNamespace)) {
            $this->error(['"' . $sNamespace . '" is not in the format [a-zA-Z0-9\\\_]+']);
            return false;
        }
        return true;
    }

    // --------------------------------------------------------------------------

    /**
     * Sets the URL
     */
    protected function setVarUrl(): void
    {
        $this->sUrl = trim($this->oInput->getOption('url'));
        if (!$this->sUrl) {
            $this->sUrl = trim($this->askForUrl());
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Requests the URL from the user
     *
     * @param string|null $sDefault The default URL
     *
     * @return mixed
     */
    protected function askForUrl(string $sDefault = null)
    {
        return $this->ask(
            'URL',
            $sDefault
        );
    }

    // --------------------------------------------------------------------------

    /**
     * Sets the description
     */
    protected function setVarDescription(): void
    {
        $this->sDescription = trim($this->oInput->getOption('description'));
        if (!$this->sDescription) {
            $this->sDescription = trim($this->askForDescription());
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Requests the description from the user
     *
     * @param string|null $sDefault The default description
     *
     * @return mixed
     */
    protected function askForDescription(string $sDefault = null)
    {
        return $this->ask(
            'Description',
            $sDefault
        );
    }
}
