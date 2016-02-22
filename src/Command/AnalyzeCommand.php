<?php

namespace ColinODell\VarPackagistSearch\Command;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AnalyzeCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('analyze')
            ->setDescription('Analyzes the code at the given path')
            ->addArgument('path', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dir = $input->getArgument('path');
        // Ensure this ends with a slash
        if (substr($dir, -1, 1) !== '/') {
            $dir .= '/';
        }

        $results = [
            'publicCount' => 0,
            'varCount' => 0,
            'varUsagesByPath' => [],
        ];

        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($it as $file) {
            if (!preg_match('/\.php$/', $file)) {
                continue;
            }

            $subPath = substr($file, strlen($dir));

            $code = file_get_contents($file);
            $tokens = token_get_all($code);
            $tokenCount = count($tokens);
            for ($i = 0; $i < $tokenCount; $i++) {
                $token = $tokens[$i];
                if (is_array($token)) {
                    if ($token[0] === T_PUBLIC) {
                        // Is this for a variable?
                        // Advance through any whitespace or static tokens until we hit a variable or something else
                        for ($i++; $i < $tokenCount; $i++) {
                            $token = $tokens[$i];
                            if (is_array($token)) {
                                if ($token[0] === T_WHITESPACE || $token[0] === T_STATIC) {
                                    continue;
                                }
                                if ($token[0] === T_VARIABLE) {
                                    $results['publicCount']++;
                                }
                            }
                            break;
                        }
                    } elseif ($token[0] === T_VAR) {
                        $results['varCount']++;
                        if (!isset($results['varUsagesByPath'][$subPath])) {
                            $results['varUsagesByPath'][$subPath] = 1;
                        } else {
                            $results['varUsagesByPath'][$subPath]++;
                        }
                    }
                }
            }
        }

        $output->write(json_encode($results));
        exit(0);
    }
}
