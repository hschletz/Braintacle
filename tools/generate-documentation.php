#!/usr/bin/php
<?php
/**
 * Generate/update API documentation.
 *
 * Copyright (C) 2011-2013 Holger Schletz <holger.schletz@web.de>
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
 * USAGE: generate-documentation.php [phpDocumentor2 path]
 *
 * If no argument is given, phpDocumentor is invoked via the 'phpdoc' command.
 * If this is not available or a different version (a development snapshot, for
 * example) should be used, specify the path to the phpDocumentor installation.
 */

error_reporting(-1);


// All paths are relative to this script's parent directory
$basePath = realpath(dirname(dirname(__FILE__)));

// Determine phpDocumentor invocation method
if (isset($_SERVER['argv'][1])) {
    $phpDocCmd = 'php ' . realpath($_SERVER['argv'][1] . '/bin/phpdoc.php');
} else {
    $phpDocCmd = 'phpdoc';
}

$cmd = array(
    $phpDocCmd,
    'run',
    '--progressbar',
    '--config',
    realpath("$basePath/doc/api/phpdoc.xml"),
    '--sourcecode',
);
$cmd = implode(' ', $cmd);
system($cmd, $result);
if ($result) {
    print "ERROR: phpDocumentor returned with error code $result.\n";
    print "Command line was:\n\n";
    print "$cmd\n\n";
    exit(1);
}
