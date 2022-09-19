#!/usr/bin/php
<?php

/**
 * Run all unit tests in appropriate order (lower level stuff first)
 *
 * Copyright (C) 2011-2022 Holger Schletz <holger.schletz@web.de>
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option)
 * any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for
 * more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

namespace TestRunner;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\SingleCommandApplication;
use Symfony\Component\Process\Process;

error_reporting(-1);

require_once(__DIR__ . '/../vendor/autoload.php');

$application = new SingleCommandApplication();
$application->setDescription('Braintacle test runner');
$application->addOption(
    'modules',
    'm',
    InputOption::VALUE_REQUIRED,
    'Comma-separated list of modules to test (case insensitive), test all modules if not set'
);
$application->addOption(
    'filter',
    'f',
    InputOption::VALUE_REQUIRED,
    'Run only tests whose names match given regex'
);
$application->addOption(
    'stop',
    's',
    InputOption::VALUE_NONE,
    'Stop after first error'
);
$application->addOption(
    'databases',
    'd',
    InputOption::VALUE_OPTIONAL,
    'Comma-separated list of INI sections with database config (use all sections if empty)',
    ''
);
$application->addOption(
    'coverage',
    'c',
    InputOption::VALUE_NONE,
    'Generate code coverage report (slow, requires Xdebug extension)'
);
$application->addOption(
    'xdebug',
    'x',
    InputOption::VALUE_NONE,
    'Activate Xdebug step debugging'
);
$application->setCode(new Run());
$application->run();

class Run
{
    public function __invoke(InputInterface $input, OutputInterface $output)
    {
        $modules = $this->getModules($input->getOption('modules'));
        $databases = $this->getDatabases($input->getOption('databases'));
        $this->runTests(
            $output,
            $modules,
            $databases,
            $input->getOption('filter'),
            $input->getOption('stop'),
            $input->getOption('coverage'),
            $input->getOption('xdebug')
        );
    }

    protected function getModules(?string $modulesOption): array
    {
        $modulesAvailable = [
            'Library',
            'Database',
            'Model',
            'Console',
            'Protocol',
            'Tools',
        ];

        $modules = [];
        if ($modulesOption) {
            foreach (explode(',', $modulesOption) as $module) {
                // Case insensitive test for valid module name
                $moduleFiltered = preg_grep('/^' . preg_quote($module, '/') . '$/i', $modulesAvailable);
                if ($moduleFiltered) {
                    $modules[] = array_shift($moduleFiltered);
                } else {
                    throw new \InvalidArgumentException("Invalid module name: $module");
                }
            }
            $modules = array_unique($modules);
        } else {
            // No module requested, test all modules in default order
            $modules = $modulesAvailable;
        }

        return $modules;
    }

    protected function getDatabases(?string $databaseOption): array
    {
        $databases = [];
        if ($databaseOption === '') {
            // Database option not set: use builtin default config
            $databases[] = null;
        } else {
            // Get available sections
            $reader = new \Laminas\Config\Reader\Ini();
            $config = $reader->fromFile(__DIR__ . '/../config/braintacle.ini');

            // Remove reserved sections
            unset($config['database']); // Production database cannot be used
            unset($config['debug']);

            if ($databaseOption === null) {
                // database option set without values: use all sections
                $databases = $config;
            } else {
                // Comma-separated list: validate and add each requested section
                foreach (explode(',', $databaseOption) as $section) {
                    if (!isset($config[$section])) {
                        throw new \InvalidArgumentException("Invalid config section: $section");
                    }
                    $databases[$section] = $config[$section];
                }
            }
        }

        return $databases;
    }

    protected function runTests(
        OutputInterface $output,
        array $modules,
        array $databases,
        ?string $filter,
        bool $stop,
        bool $coverage,
        bool $xdebug
    ) {
        foreach ($modules as $module) {
            foreach ($databases as $name => $database) {
                $message = "\nRunning tests on $module module with ";
                if ($database) {
                    $message .= "config '$name'";
                } else {
                    $message .= 'default config';
                }
                $message .= "\n\n";
                $output->write($message);

                $this->runTest($output, $module, $database, $filter, $stop, $coverage, $xdebug);
            }
        }
    }

    protected function runTest(
        OutputInterface $output,
        string $module,
        ?array $database,
        ?string $filter,
        bool $stop,
        bool $coverage,
        bool $xdebug
    ) {
        $xdebugMode = [];
        if ($coverage) {
            $xdebugMode[] = 'coverage';
        }
        if ($xdebug) {
            $xdebugMode[] = 'debug';
        }

        $cmd = [(new \Symfony\Component\Process\PhpExecutableFinder())->find()];
        if ($xdebugMode) {
            $cmd[] = '-d zend_extension=xdebug.' . PHP_SHLIB_SUFFIX;
        }
        // Avoid vendor/bin/phpunit for Windows compatibility
        $cmd[] = __DIR__ . '/../vendor/phpunit/phpunit/phpunit';
        $cmd[] = '-c';
        $cmd[] = __DIR__ . "/../module/$module/phpunit.xml";
        $cmd[] = '--colors=always';
        $cmd[] = '--disallow-test-output';
        if ($coverage) {
            $cmd[] = '--coverage-html=';
            $cmd[] = __DIR__ . "/../doc/CodeCoverage/$module";
        }
        if ($filter) {
            $cmd[] = '--filter';
            $cmd[] = $filter;
        }
        if ($stop) {
            $cmd[] = '--stop-on-error';
        }

        $env = ['VAR_DUMPER_FORMAT' => 'html']; // Prevent VarDumper from writing to STDOUT in CLI.
        if ($xdebugMode) {
            $env['XDEBUG_MODE'] = implode(',', $xdebugMode);
        }
        if ($database) {
            $env['BRAINTACLE_TEST_DATABASE'] = json_encode($database);
        }

        $process = new Process($cmd);
        $process->setTimeout(null);
        $process->start(null, $env);
        foreach ($process as $data) {
            $output->write($data);
        }

        if (!$process->isSuccessful()) {
            throw new \RuntimeException(
                sprintf(
                    "Unit tests for module '%s' failed with status %d. Aborting.",
                    $module,
                    $process->getExitCode()
                )
            );
        }
    }
}
