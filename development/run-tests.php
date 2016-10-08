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
            'module|m=w' => 'run only tests for given module',
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

$doCoverage = $opts->coverage ?: false;

if ($opts->module) {
    testModule(ucfirst($opts->module), $opts->filter, $doCoverage);
} else {
    $modules = array(
        'Library',
        'Database',
        'Model',
        'Protocol',
        'Console',
        'DatabaseManager',
        'DecodeInventory',
        'Export',
        'PackageBuilder',
    );
    foreach ($modules as $module) {
        testModule($module, $opts->filter, $doCoverage);
    }
}
