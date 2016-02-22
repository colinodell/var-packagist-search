<?php

require_once 'vendor/autoload.php';

use Composer\DependencyResolver\Pool;
use Composer\Factory;
use Composer\IO\NullIO;
use Composer\Installer;
use Composer\Package\Version\VersionSelector;
use Composer\Repository\CompositeRepository;

error_reporting(E_ERROR);

$tmpDir = sys_get_temp_dir();
$resultsFile = __DIR__.'/results.json';

$repos = Factory::createDefaultRepositories(new NullIO());
$pool = new Pool('dev');
$pool->addRepository(new CompositeRepository($repos));
$versionSelector = new VersionSelector($pool);

function getTopPackages() {
    foreach (range(1, 2000) as $page) {
        $json = json_decode(file_get_contents('https://packagist.org/explore/popular.json?page='.$page), true);
        foreach ($json['packages'] as $package) {
            yield $package['name'];
        }
    }
}

$start = microtime(true);
$stats = [];
foreach (getTopPackages() as $i => $packageName) {
    file_put_contents($resultsFile, json_encode($stats));

    $dir
    if (file_exists($dir) && strpos($dir, '/tmp/') === 0 && strpos($dir, '..') === false) {
        exec('rm -rf ' . $dir);
    }

    mkdir($dir);

    $stats[$i] = ['package' => $packageName];

    printf("[%d] Looking up best version for %s: ", $i, $packageName);

    $package = $versionSelector->findBestCandidate($packageName);
    if ($package === false) {
        $stats[$i]['error'] = 'Could not find suitable package';
        continue;
    }

    $version = $package->getVersion();
    printf("%s\n", $version);
    $stats[$i]['version'] = $version;

    printf("Installing... ");

    $output = [];
    $returnVal = 0;
    $command = sprintf('php -d memory_limit=2G %s/install_package.php %s %s %s', __DIR__, $package->getName(), $package->getVersion(), $dir);
    exec($command, $output, $returnVal);

    if ($returnVal !== 0) {
        printf("FAILED!\n".implode("\n", $output));
        $stats[$i]['error'] = 'Install failed';
        continue;
    }

    printf("done!\nAnalyzing... ");
    $results = runTests($dir);
    printf(" done!\n");
    printf("  - public: %d\n  - var:    %d\n", $results['public'], $results['var']);

    $stats[$i]['stats'] = $results;

    unset($package);
    if (strpos($dir, '/tmp/') === 0 && strpos($dir, '..') === false) {
        exec('rm -rf ' . $dir);
    }
    echo "\n";

    if ($i % 10 === 9) {
        printf("Memory usage: %s MB\n", number_format(memory_get_usage()/(1024*1024), 2));
        $elapsedTime = (microtime(true) - $start);
        $packagesProcessed = count($stats);
        $speedPerPackage = $elapsedTime / $packagesProcessed;
        printf("Average speed: %d seconds per package\n",  $speedPerPackage);
        $packagesRemaining = 30000 - $packagesProcessed;
        $timeRemaining = $speedPerPackage * $packagesRemaining;
        $hours = floor($timeRemaining / 3600);
        $minutes = floor(($timeRemaining - ($hours*3600))/60);
        printf("Estimated time to completion: %d hours and %d minutes\n\n", $hours, $minutes);
    }
}

file_put_contents($resultsFile, json_encode($stats));

function createComposerJson(\Composer\Package\PackageInterface $package) {
    return json_encode([
        'require' => [$package->getName() => $package->getVersion()],
        'minimum-stability' => 'dev',
    ]);
}

function runTests($dir) {
    $output = [];
    $returnVal = 0;
    $command = sprintf('php -d memory_limit=2G %s/analyze.php %s', __DIR__, $dir);
    exec($command, $output, $returnVal);

    $results = json_decode(implode("\n", $output), true);

    return $results;
}
