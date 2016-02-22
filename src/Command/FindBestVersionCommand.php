<?php

namespace ColinODell\VarPackagistSearch\Command;

use Composer\DependencyResolver\Pool;
use Composer\Factory;
use Composer\IO\NullIO;
use Composer\Package\Version\VersionSelector;
use Composer\Repository\CompositeRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FindBestVersionCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('find-best-version')
            ->setDescription('Processes the packages')
            ->addArgument('packageName', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $repos = Factory::createDefaultRepositories(new NullIO());
        $pool = new Pool('dev');
        $pool->addRepository(new CompositeRepository($repos));
        $versionSelector = new VersionSelector($pool);

        $packageName = $input->getArgument('packageName');

        $package = $versionSelector->findBestCandidate($packageName);
        if ($package === false) {
            exit(1);
        }

        $output->write($package->getVersion());
        exit(0);
    }
}
