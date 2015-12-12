#!/usr/bin/php
<?php
/**
 * Generate/update API documentation.
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
 *
 * @package Tools
 */

/*
 * USAGE: generate-documentation.php [ApiGen path]
 *
 * If no argument is given, Apigen is invoked via the 'apigen' command. If this
 * is not available or a different version (a development snapshot, for example)
 * should be used, specify the path to the ApiGen script/PHAR.
 */

error_reporting(-1);

// Determine ApiGen invocation method
if (isset($_SERVER['argv'][1])) {
    $apigenCmd = $_SERVER['argv'][1];
} else {
    // Use apigen from vendor directory if available
    $apigenCmd = __DIR__ . '/../vendor/bin/apigen';
    if (file_exists($apigenCmd)) {
        $apigenCmd = escapeshellarg(realpath($apigenCmd));
    } else {
        $apigenCmd = 'apigen'; // fall back to globally installed version
    }
}

// Invoke ApiGen
$cmd = array(
    $apigenCmd,
    'generate',
    '-s ' . escapeshellarg(realpath(__DIR__ . '/../module')),
    '-d ' . escapeshellarg(realpath(__DIR__ . '/../doc') . DIRECTORY_SEPARATOR . 'api'),
    '--deprecated',
    '--todo',
    '--php',
    '--exclude ' . escapeshellarg('*/Test'),
    '--title ' . escapeshellarg('Braintacle API documentation'),
);
$cmd = implode(' ', $cmd);
passthru($cmd, $result); // system() would swallow or delay some output
if ($result) {
    print "ERROR: ApiGen returned with error code $result.\n";
    print "Command line was:\n\n";
    print "$cmd\n\n";
    exit(1);
}
