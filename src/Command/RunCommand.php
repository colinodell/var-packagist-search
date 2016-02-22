<?php

namespace ColinODell\VarPackagistSearch\Command;

use ColinODell\VarPackagistSearch\Package;
use ColinODell\VarPackagistSearch\PopularPackages;
use ColinODell\VarPackagistSearch\ProcessDebugInfo;
use ColinODell\VarPackagistSearch\ResultCollection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RunCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('run')
            ->setDescription('Processes the packages')
            ->addArgument('limit', InputArgument::REQUIRED)
            ->addOption('offset', null, InputOption::VALUE_REQUIRED, '', 0)
            ->addOption('step', null, InputOption::VALUE_REQUIRED, '', 1);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $limit = $input->getArgument('limit');
        $offset = $input->getOption('offset');
        $step = $input->getOption('step');

        // Obtain the list of packages if we don't have it already
        $packageList = PopularPackages::getList($limit);

        $results = new ResultCollection(sprintf('results-%d-%d-%d.%d.json', $limit, $offset, $step, time()));
        $maxPackageCount = ceil(($limit - $offset) / $step);
        $debug = new ProcessDebugInfo($results, $maxPackageCount);

        for ($i = $offset; $i < $limit; $i += $step) {
            $package = new Package($packageList[$i]);

            $dir = $package->getInstallationPath();
            $this->createInstallationDirectory($dir);

            $output->write(sprintf("[%d] Looking up best version for <info>%s</info>: ", $i, $package->getName()));
            if (!$this->findBestVersion($package)) {
                $error = 'Could not find a suitable package';
                $package->setError($error);
                $output->writeln('<error>'.$error.'</error>');

                $results->addResult($i, $package);

                continue;
            }

            $output->writeln('<info>'.$package->getVersion().'</info>');

            $output->write('Installing... ');
            if (!$this->installPackage($package)) {
                $error = 'Installation failed';
                $package->setError($error);
                $output->writeln('<error>'.$error.'</error>');

                $results->addResult($i, $package);
                $this->cleanDirectory($dir);
                continue;
            }

            $output->writeln('<info>done!</info>');
            $output->write('Analyzing... ');

            if (!$this->runAnalysis($package)) {
                $output->writeln('<error>failed</error>');
            } else {
                $results->addResult($i, $package);
                $output->writeln('<info>done!</info>');
                $output->writeln(' - public: ' . $package->getPublicCount());
                $output->writeln(' - var:    ' . $package->getVarCount());
            }

            $output->writeln('');

            $this->cleanDirectory($dir);

            if (rand(0, 9) === 0) {
                $debug->render($output);
                $output->writeln('');
            }
        }
    }

    /**
     * @param string $dir
     */
    private function createInstallationDirectory($dir)
    {
        $this->cleanDirectory($dir);
        mkdir($dir);
    }

    /**
     * @param $dir
     */
    private function cleanDirectory($dir)
    {
        // Clean up the installation directory if it exists
        if (file_exists($dir) && strpos($dir, '/tmp/') === 0 && strpos($dir, '..') === false) {
            exec('rm -rf ' . $dir);
        }
    }

    /**
     * @param string   $commandName
     * @param string[] $args
     * @param int      $maxMemoryInGb
     * @return array
     */
    private function executeCommand($commandName, $args = [], $maxMemoryInGb = 1)
    {
        $output = [];
        $returnVal = 0;
        $command = sprintf(
            'php -d memory_limit=%dG %s %s %s',
            $maxMemoryInGb,
            __DIR__.'/../../app.php',
            $commandName,
            implode(' ', $args)
        );

        exec($command, $output, $returnVal);

        $output = implode("\n", $output);

        return [$returnVal, $output];
    }

    /**
     * @param Package $package
     *
     * @return bool
     */
    private function findBestVersion(Package $package)
    {
        list($result, $output) = $this->executeCommand('find-best-version', [$package->getName()]);
        if ($result !== 0) {
            return false;
        }

        $version = trim($output);
        $package->setVersion($version);

        return true;
    }

    /**
     * @param Package $package
     *
     * @return bool
     */
    private function installPackage(Package $package)
    {
        list($result) = $this->executeCommand('install-package', [$package->getName(), $package->getVersion(), $package->getInstallationPath()], 3);

        return $result === 0;
    }

    /**
     * @param Package $package
     *
     * @return bool
     */
    private function runAnalysis(Package $package)
    {
        list($result, $output) = $this->executeCommand('analyze', [$package->getInstallationPath()]);
        if ($result !== 0) {
            return false;
        }

        $data = json_decode($output, true);
        $package->setPublicCount($data['publicCount']);
        $package->setVarCount($data['varCount']);
        $package->setVarUsages($data['varUsagesByPath']);

        return true;
    }
}
