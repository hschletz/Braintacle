#!/usr/bin/php
<?php
/**
 * Create a package from the command line
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
/**
 * This script creates a package from the command line. This is useful if a file
 * is too big to be uploaded to the webserver. It can also be used as part of a
 * package builder script.
 *
 * It is limited to the package builder defaults. Only the name and the file
 * itself can be specified.
 *
 * Don't forget to change permissions/ownership of the generated directory and
 * files. Otherwise the webserver won't be able to read and/or delete them.
 */

// Force argument to be specified
if (count($_SERVER['argv']) != 3) {
    print "USAGE: createpackage.php <name> <file>\n";
    exit(1);
}
$name = $_SERVER['argv'][1];
$file = $_SERVER['argv'][2];

// Determine file type (ZIP or not)
$zip = @zip_open($file);
if (is_resource($zip)) {
    $type = 'application/zip';
} else {
    $type = 'application/octet-stream';
}

// Set up environment
require(realpath(dirname(dirname(__FILE__)) . '/library/Braintacle/Application.php'));
Braintacle_Application::init();

// Create Package
$package = new Model_Package;
$package->fromArray(
    array(
        'Name' => $name,
        'Comment' => null,
        'FileName' => basename($file),
        'FileType' => $type,
        'FileLocation' => $file,
        'Priority' => Model_Config::get('DefaultPackagePriority'),
        'Platform' => Model_Config::get('DefaultPlatform'),
        'DeployAction' => Model_Config::get('DefaultAction'),
        'ActionParam' => Model_Config::get('DefaultActionParam'),
        'Warn' => Model_Config::get('DefaultWarn'),
        'WarnMessage' => Model_Config::get('DefaultWarnMessage'),
        'WarnCountdown' => Model_Config::get('DefaultWarnCountdown'),
        'WarnAllowAbort' => Model_Config::get('DefaultWarnAllowAbort'),
        'WarnAllowDelay' => Model_Config::get('DefaultWarnAllowDelay'),
        'UserActionRequired' => Model_Config::get('DefaultUserActionRequired'),
        'UserActionMessage' => Model_Config::get('DefaultUserActionMessage'),
        'MaxFragmentSize' => Model_Config::get('DefaultMaxFragmentSize'),
        'InfoFileUrlPath' => Model_Config::get('DefaultInfoFileLocation'),
        'DownloadUrlPath' => Model_Config::get('DefaultDownloadLocation'),
        'CertFile' => Model_Config::get('DefaultCertificate'),
    )
);
if ($package->build(false)) {
    $errType = 'WARNING: ';
    $message = "Package successfully built.\n";
    $path = $package->getPath();
} else {
    $errType = 'ERROR: ';
    $message = "The package has not been built.\n";
}
foreach ($package->getErrors() as $msg) {
    print $errType;
    print $msg;
    print "\n";
}
print "\n";
print $message;
