<?php

use Composer\Factory;
use Composer\Installer;
use Composer\IO\NullIO;

require_once 'vendor/autoload.php';

$packageName = $argv[1];
$version = $argv[2];
$dir = $argv[3];

$composerJson = $dir.'/composer.json';
file_put_contents($composerJson, json_encode([
    'require' => [$packageName => $version],
    'minimum-stability' => 'dev',
]));

$factory = new Factory();
$io = new NullIO();
$composer = $factory->createComposer($io, $composerJson, false, $dir);

try {
    $install = Installer::create($io, $composer);

    $install
        ->setPreferSource(false)
        ->setPreferDist(true)
        ->setDevMode(false)
        ->setOptimizeAutoloader(false)
        ->setClassMapAuthoritative(false)
        ->setUpdate(true)
        ->setUpdateWhitelist([$packageName])
        ->setWhitelistDependencies(true)
        ->setIgnorePlatformRequirements(true)
    ;

    $status = $install->run();
    echo 'Installed successfully';
    exit(0);
} catch (Exception $ex) {
    var_dump($ex);
    exit(1);
}

