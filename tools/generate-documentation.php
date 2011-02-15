#!/usr/bin/php
<?php
/**
 * Generate/update API documentation.
 *
 * $Id$
 *
 * Copyright (C) 2011 Holger Schletz <holger.schletz@web.de>
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

error_reporting(-1);

// All paths are relative to this script's parent directory
$basePath = realpath(dirname(dirname(__FILE__)));

print 'Running phpdoc on source files... ';
$cmd = array(
    'phpdoc',
    '--directory',
    realpath("$basePath/library/Braintacle") . ',' .
    realpath("$basePath/application/controllers/helpers") . ',' .
    realpath("$basePath/application/forms") . ',' .
    realpath("$basePath/application/models") . ',' .
    realpath("$basePath/application/views/helpers"),
    '--target',
    realpath("$basePath/doc/api"),
    '--output',
    'HTML:Smarty:default',
    '--undocumentedelements',
    'on',
    '--title',
    'Braintacle API documentation',
    '--sourcecode',
);
$cmd = implode(' ', $cmd);
exec($cmd, $output, $result);
if ($result) {
    print "ERROR: phpdoc returned with error code $result.\n";
    print "Command line was:\n\n";
    print "$cmd\n\n";
    exit(1);
} else {
    print "done.\n";
    exit(0);
}
