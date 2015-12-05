#!/usr/bin/php
<?php
/**
 * Run all unit tests in appropriate order (lower level stuff first)
 *
 * Copyright (C) 2011-2015 Holger Schletz <holger.schletz@web.de>
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

/**
 * Run tests for specified module
 * 
 * @param string $module Module name
 * @param string $filter optional filter for tests, used as phpunit's --filter option
 */
function testModule($module, $filter=null)
{
    global $vendorBin;
    global $doCoverage;

    print "\nRunning tests on $module module\n\n";
    $cmd = PHP_BINARY;
    if ($doCoverage) {
        $cmd .= ' -dzend_extension=xdebug.' . PHP_SHLIB_SUFFIX;
    }
    $cmd .= " {$vendorBin}phpunit -c module/$module/phpunit.xml --colors --report-useless-tests --disallow-test-output";
    if ($doCoverage) {
       $cmd .= " --coverage-html=doc/CodeCoverage/$module";
    }
    if ($filter) {
        $cmd .= ' --filter ' .escapeshellarg($filter);
    }

    // Pass descriptors explicitly to make PHPUnit 4.4 recognize a TTY which is
    // required for color output and terminal size detection.
    if ($handle = proc_open($cmd, array(STDIN, STDOUT, STDERR), $pipes)) {
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

// Change to application root directory to allow relative paths
chdir(dirname(__DIR__));

// Special application environment, allows application code to skip actions not
// appropriate in a unit test environment.
putenv('APPLICATION_ENV=test');

// Use phpunit from vendor directory if available
$vendorBin = __DIR__ . '/../vendor/phpunit/phpunit/';
if (!file_exists($vendorBin . 'phpunit')) {
    $vendorBin = ''; // fall back to globally installed version
}

// Look for --coverage argument anywhere in the command line, remove it for subsequent processing
$args = $_SERVER['argv'];
$coveragePos = array_search('--coverage', $args);
if ($coveragePos === false) {
    $doCoverage = false;
} else {
    $doCoverage = true;
    array_splice($args, $coveragePos, 1);
}

if (count($args) >= 2) {
    // Run tests for explicit module and optional filter
    testModule(ucfirst($args[1]), @$args[2]);
} else {
    // Run tests for all modules that have tests defined
    testModule('Library');
    testModule('Database');
    testModule('Model');
    testModule('Protocol');
    testModule('Console');
    testModule('DatabaseManager');
    testModule('Export');
}
