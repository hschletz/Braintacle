#!/usr/bin/php
<?php
/**
 * Run all unit tests in appropriate order (lower level stuff first)
 *
 * Copyright (C) 2011-2019 Holger Schletz <holger.schletz@web.de>
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

error_reporting(-1);

require_once(__DIR__ . '/../vendor/autoload.php');

/**
 * Run tests for specified module
 *
 * @param string $module Module name
 * @param string $filter if not empty, pass to phpunit's --filter option
 * @param mixed $database Array with database config. If empty, default config is used.
 * @param bool $doCoverage generate code coverage report
 */
function testModule($module, $filter, $database, $doCoverage)
{
    $cmd = [(new \Symfony\Component\Process\PhpExecutableFinder)->find()];
    if ($doCoverage) {
        $cmd[] = '-d zend_extension=xdebug.' . PHP_SHLIB_SUFFIX;
    }
    // Avoid vendor/bin/phpunit for Windows compatibility
    $cmd[] = \Library\Application::getPath('vendor/phpunit/phpunit/phpunit');
    $cmd[] = '-c';
    $cmd[] = \Library\Application::getPath("module/$module/phpunit.xml");
    $cmd[] = '--colors=always';
    $cmd[] = '--disallow-test-output';
    if ($doCoverage) {
        $cmd[] = '--coverage-html=';
        $cmd[] = \Library\Application::getPath("doc/CodeCoverage/$module");
    }
    if ($filter) {
        $cmd[] = '--filter';
        $cmd[] = $filter;
    }

    $process = new \Symfony\Component\Process\Process($cmd);
    $process->setTimeout(null);
    $process->start(
        null,
        $database ? ['BRAINTACLE_TEST_DATABASE' => json_encode($database)] : []
    );
    foreach ($process as $type => $data) {
        print $data;
    }

    if (!$process->isSuccessful()) {
        printf("\n\nUnit tests for module '%s' failed with status %d. Aborting.\n", $module, $process->getExitCode());
        exit(1);
    }
}

try {
    $opts = new \Zend\Console\Getopt(
        array(
            'modules|m=s' => 'comma-separated list of modules to test (case insensitive), test all modules if not set',
            'filter|f=s' => 'run only tests whose names match given regex',
            'database|d-s' => 'comma-separated list of INI sections with database config (use all sections if empty)',
            'coverage|c' => 'generate code coverage report (slow, requires Xdebug extension)',
        )
    );
    $opts->parse();
    if ($opts->getRemainingArgs()) {
        throw new \Zend\Console\Exception\RuntimeException(
            'Non-option arguments not allowed',
            $opts->getUsageMessage()
        );
    }
} catch (\Zend\Console\Exception\RuntimeException $e) {
    print $e->getUsageMessage();
    exit(1);
}

// Generate list of available modules.
// The following basic modules are tested first. Other modules are added
// dynamically.
$modulesAvailable = array(
    'Library',
    'Database',
    'Model',
);
foreach (new \FilesystemIterator(__DIR__ . '/../module') as $entry) {
    if ($entry->isDir() and !in_array($entry->getFilename(), $modulesAvailable)) {
        $modulesAvailable[] = $entry->getFilename();
    }
}

// Compose list of modules to test
$modules = array();
if ($opts->modules) {
    foreach (explode(',', $opts->modules) as $module) {
        // Case insensitive test for valid module name
        $moduleFiltered = preg_grep('/^' . preg_quote($module, '/') . '$/i', $modulesAvailable);
        if ($moduleFiltered) {
            $modules[] = array_shift($moduleFiltered);
        } else {
            print "Invalid module name: $module\n";
            exit(1);
        }
    }
    $modules = array_unique($modules);
} else {
    // No module requested, test all modules
    $modules = $modulesAvailable;
}

// Compose list of database configurations to test
$databases = array();
if ($opts->database) {
    // Get available sections
    $reader = new \Zend\Config\Reader\Ini;
    $config = $reader->fromFile(\Library\Application::getPath('config/braintacle.ini'));

    // Remove reserved sections
    unset($config['database']); // Production database cannot be used
    unset($config['debug']);

    if (is_string($opts->database)) {
        // Comma-separated list: validate and add each requested section
        foreach (explode(',', $opts->database) as $section) {
            if (!isset($config[$section])) {
                print "Invalid config section: $section\n";
                exit(1);
            }
            $databases[$section] = $config[$section];
        }
    } else {
        // database option set without values: use all sections
        $databases = $config;
    }
} else {
    // Database option not set: use builtin default config
    $databases[] = null;
}

// Run tests for all requested modules
foreach ($modules as $module) {
    foreach ($databases as $name => $database) {
        print "\nRunning tests on $module module with ";
        if ($database) {
            print "config '$name'";
        } else {
            print 'default config';
        }
        print "\n\n";

        testModule($module, $opts->filter, $database, ($opts->coverage ?: false));
    }
}
