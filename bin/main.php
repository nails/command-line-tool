#!/usr/bin/env php
<?php

namespace Nails\Cli;

use Nails\Cli\Helper\Directory;
use Nails\Cli\Helper\Updates;
use Symfony\Component\Console\Application;
use Symfony\Component\Finder\Finder;

// --------------------------------------------------------------------------

require __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
define('BASEPATH', Directory::normalize(__DIR__ . '/../'));

// --------------------------------------------------------------------------

$oApp    = new Application();
$oFinder = new Finder();

//  Auto-load commands
$sBasePath = BASEPATH . 'src';
$oFinder->files()->in($sBasePath . '/Command');

foreach ($oFinder as $oFile) {
    $sCommand = $oFile->getPath() . DIRECTORY_SEPARATOR . $oFile->getBasename('.php');
    $sCommand = str_replace($sBasePath, 'Nails/Cli', $sCommand);
    $sCommand = str_replace(DIRECTORY_SEPARATOR, '\\', $sCommand);

    if ($sCommand !== 'Nails\\Cli\\Command\\Base') {
        $oApp->add(new $sCommand());
    }
}

$oApp->setName('Nails Command Line Tool');
$oApp->setVersion(Updates::getCurrentVersion());
$oApp->run();
