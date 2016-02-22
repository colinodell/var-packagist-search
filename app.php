#!/usr/bin/env php
<?php

require __DIR__.'/vendor/autoload.php';

use ColinODell\VarPackagistSearch\Command\AnalyzeCommand;
use ColinODell\VarPackagistSearch\Command\FindBestVersionCommand;
use ColinODell\VarPackagistSearch\Command\InstallPackageCommand;
use ColinODell\VarPackagistSearch\Command\ReportCommand;
use ColinODell\VarPackagistSearch\Command\RunCommand;
use Symfony\Component\Console\Application;

$application = new Application();
$application->add(new AnalyzeCommand());
$application->add(new FindBestVersionCommand());
$application->add(new InstallPackageCommand());
$application->add(new ReportCommand());
$application->add(new RunCommand());
$application->run();
