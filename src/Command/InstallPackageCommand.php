<?php

namespace ColinODell\VarPackagistSearch\Command;

use Composer\Factory;
use Composer\Installer;
use Composer\IO\NullIO;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InstallPackageCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('install-package')
            ->setDescription('Installs the given package')
            ->addArgument('packageName', InputArgument::REQUIRED)
            ->addArgument('version', InputArgument::REQUIRED)
            ->addArgument('path', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $packageName = $input->getArgument('packageName');
        $version = $input->getArgument('version');
        $dir = $input->getArgument('path');

        $composerJson = $dir.'/composer.json';
        file_put_contents($composerJson, json_encode([
            'require' => [$packageName => $version],
            'minimum-stability' => 'dev',
        ]));

        $factory = new Factory();
        $io = new NullIO();
        chdir($dir);
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
                ->setWhitelistDependencies(false)
                ->setIgnorePlatformRequirements(false)
            ;

            $status = $install->run();
            exit($status);
        } catch (\Exception $ex) {
            var_dump($ex);
            exit(1);
        }
    }
}
