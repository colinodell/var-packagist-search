<?php

namespace ColinODell\VarPackagistSearch\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ReportCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('report')
            ->setDescription('Generates a final report based on the results')
            ->addArgument('files', InputArgument::REQUIRED | InputArgument::IS_ARRAY);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $files = $input->getArgument('files');

        $data = [];
        foreach ($files as $file) {
            $set = json_decode(file_get_contents($file), true);
            $data = $data + $set;
        }

        ksort($data);

        $stats = [
            'totalPackages' => count($data),
            'analyzedPackages' => 0,
            'failedPackages' => 0,
            'totalPublics' => 0,
            'totalVars' => 0,
            'packagesWithPublics' => 0,
            'packagesWithVars' => 0,
            'packagesWithNeither' => 0,
            'packagesWithBoth' => 0,
            'varUsagesSelf' => 0,
            'varUsagesDeps' => 0,
            'packagesWithSelfVarUsage' => 0,
            'packagesWithDepVarUsage' => 0,
        ];

        $pathsUsingVar = [];

        foreach ($data as $package) {
            if (isset($package['error'])) {
                $stats['failedPackages']++;
                continue;
            }

            $stats['analyzedPackages']++;
            $hasOneOrTheOther = 0;
            if ($count = $package['stats']['public']) {
                $stats['packagesWithPublics']++;
                $stats['totalPublics'] += $count;
                $hasOneOrTheOther++;
            }
            if ($count = $package['stats']['var']) {
                $stats['packagesWithVars']++;
                $stats['totalVars'] += $count;
                $hasOneOrTheOther++;
            }

            if ($hasOneOrTheOther === 0) {
                $stats['packagesWithNeither']++;
            } elseif ($hasOneOrTheOther === 2) {
                $stats['packagesWithBoth']++;
            }

            $thisPackageVarUsages = [];

            if (!empty($package['stats']['varUsages'])) {
                foreach ($package['stats']['varUsages'] as $path => $count) {
                    $pathParts = explode('/', $path);
                    $pathParts = array_splice($pathParts, 0, 3);
                    $pathKey = implode('/', $pathParts);

                    if (!isset($pathsUsingVar[$pathKey])) {
                        $pathsUsingVar[$pathKey] = 0;
                    }

                    if (!isset($thisPackageVarUsages[$pathKey])) {
                        $thisPackageVarUsages[$pathKey] = 0;
                    }

                    $thisPackageVarUsages[$pathKey] += $count;
                    $pathsUsingVar[$pathKey] += $count;
                }
            }

            $usesSelf = false;
            $usesDeps = false;
            foreach ($thisPackageVarUsages as $pathKey => $count) {
                if (strpos($pathKey, 'vendor/') !== 0) {
                    $stats['varUsagesSelf'] += $count;
                    $usesSelf = true;
                } elseif (strpos($pathKey, $package['package']) > 0) {
                    $stats['varUsagesSelf'] += $count;
                    $usesSelf = true;
                } else {
                    $stats['varUsagesDeps'] += $count;
                    $usesDeps = true;
                }
            }

            if ($usesSelf) {
                $stats['packagesWithSelfVarUsage']++;
            }

            if ($usesDeps) {
                $stats['packagesWithDepVarUsage']++;
            }
        }

        $rows = [];
        foreach ($stats as $key => $value) {
            $rows[$key] = [$key, number_format($value)];
        }

        $rows['analyzedPackages'][] = number_format($stats['analyzedPackages'] / $stats['totalPackages'] * 100) . '%';
        $rows['failedPackages'][] = number_format($stats['failedPackages'] / $stats['totalPackages'] * 100) . '%';

        $rows['totalPublics'][] = number_format($stats['totalPublics'] / ($stats['totalPublics'] + $stats['totalVars']) * 100) . '%';
        $rows['totalVars'][] = number_format($stats['totalVars'] / ($stats['totalPublics'] + $stats['totalVars']) * 100) . '%';

        $rows['packagesWithPublics'][] = number_format($stats['packagesWithPublics'] / $stats['totalPackages'] * 100) . '%';
        $rows['packagesWithVars'][] = number_format($stats['packagesWithVars'] / $stats['totalPackages'] * 100) . '%';
        $rows['packagesWithBoth'][] = number_format($stats['packagesWithBoth'] / $stats['totalPackages'] * 100) . '%';
        $rows['packagesWithNeither'][] = number_format($stats['packagesWithNeither'] / $stats['totalPackages'] * 100) . '%';

        $rows['varUsagesSelf'][] = number_format($stats['varUsagesSelf'] / $stats['totalVars'] * 100) . '%';
        $rows['varUsagesDeps'][] = number_format($stats['varUsagesDeps'] / $stats['totalVars'] * 100) . '%';

        $rows['packagesWithSelfVarUsage'][] = number_format($stats['packagesWithSelfVarUsage'] / $stats['totalPackages'] * 100) . '%';
        $rows['packagesWithDepVarUsage'][] = number_format($stats['packagesWithDepVarUsage'] / $stats['totalPackages'] * 100) . '%';

        $table = new Table($output);
        $table->addRows([
            ['Total Packages', $rows['totalPackages'][1]],
            new TableSeparator(),
            ['Analyzed Packages', $rows['analyzedPackages'][1], $rows['analyzedPackages'][2]],
            ['Failed Packages', $rows['failedPackages'][1], $rows['failedPackages'][2]],
            new TableSeparator(),
            ['Packages With `public`', $rows['packagesWithPublics'][1], $rows['packagesWithPublics'][2]],
            ['Packages With `var`', $rows['packagesWithVars'][1], $rows['packagesWithVars'][2]],
            ['Packages With both', $rows['packagesWithBoth'][1], $rows['packagesWithBoth'][2]],
            ['Packages With neither', $rows['packagesWithNeither'][1], $rows['packagesWithNeither'][2]],
            new TableSeparator(),
            ['Packages with internal `var` usage', $rows['packagesWithSelfVarUsage'][1], $rows['packagesWithSelfVarUsage'][2]],
            ['Packages with dependency `var` usage', $rows['packagesWithDepVarUsage'][1], $rows['packagesWithDepVarUsage'][2]],
            new TableSeparator(),
            ['Total `public` keywords', $rows['totalPublics'][1], $rows['totalPublics'][2]],
            ['Total `var` keywords', $rows['totalVars'][1], $rows['totalVars'][2]],
            new TableSeparator(),
            ['Internal `var` usages', $rows['varUsagesSelf'][1], $rows['varUsagesSelf'][2]],
            ['Dependency `var` usages', $rows['varUsagesDeps'][1], $rows['varUsagesDeps'][2]],
        ]);
        $table->render();

        arsort($pathsUsingVar);
        $pathsUsingVar = array_slice($pathsUsingVar, 0, 25);

        $output->writeln('Top 25 worst offenders:');
        $output->writeln(print_r($pathsUsingVar, true));
    }

}
