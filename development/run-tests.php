#!/usr/bin/php
<?php
/**
 * Run all unit tests in appropriate order (lower level stuff first)
 *
 * Copyright (C) 2011-2016 Holger Schletz <holger.schletz@web.de>
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
 * @param bool $doCoverage generate code coverage report
 */
function testModule($module, $filter, $doCoverage)
{
    print "\nRunning tests on $module module\n\n";

    $baseDir = dirname(__DIR__);
    $cmd = array(PHP_BINARY);
    if ($doCoverage) {
        $cmd[] = '-d zend_extension=xdebug.' . PHP_SHLIB_SUFFIX;
    }
    // Avoid vendor/bin/phpunit for Windows compatibility
    $cmd[] = escapeshellarg(__DIR__ . '/../vendor/phpunit/phpunit/phpunit');
    $cmd[] = '-c ' . escapeshellarg("$baseDir/module/$module/phpunit.xml");
    $cmd[] = '--colors --report-useless-tests --disallow-test-output';
    if ($doCoverage) {
        $cmd[] = '--coverage-html=' . escapeshellarg("$baseDir/doc/CodeCoverage/$module");
    }
    if ($filter) {
        $cmd[] = '--filter ' . escapeshellarg($filter);
    }

    // Pass descriptors explicitly to make PHPUnit 4.4 recognize a TTY which is
    // required for color output and terminal size detection.
    if ($handle = proc_open(implode(' ', $cmd), array(STDIN, STDOUT, STDERR), $pipes)) {
        $exitCode = proc_close($handle);
        if ($exitCode) {
            printf("\n\nUnit tests for module '%s' failed with status %d. Aborting.\n", $module, $exitCode);
            exit(1);
        } else {
            print "\n";
        }
    } else {
        print "Could not invoke PHPUnit. Aborting.\n";
        exit(1);
    }
}

try {
    $opts = new \Zend\Console\Getopt(
        array(
            'modules|m=s' => 'comma-separated list of modules to test (case insensitive), test all modules if not set',
            'filter|f=s' => 'run only tests whose names match given regex',
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

// Run tests for all requested modules
foreach ($modules as $module) {
    testModule($module, $opts->filter, ($opts->coverage ?: false));
}
