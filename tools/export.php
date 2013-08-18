#!/usr/bin/php
<?php
/**
 * Export all computers as XML
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

// Report every possible error and warning
error_reporting(-1);
// Only set 1 of these options to prevent duplicate messages on the console
ini_set('display_errors', true);
ini_set('log_errors', false);
if (extension_loaded('xdebug')) {
    xdebug_disable(); // Prevents printing backtraces on validation errors
}

// All paths are relative to this script's parent directory
$basepath = realpath(dirname(__DIR__));

require_once "$basepath/module/Library/Application.php";
\Library\Application::init('Cli');

// Parse command line
$cmdLine = new Zend_Console_Getopt(
    array(
        'dir|d=s' => 'output directory (required)',
        'validate|v' => 'validate output documents, abort on error',
    )
);
try {
    $cmdLine->parse();
    if ($cmdLine->getRemainingArgs() or !$cmdLine->dir) {
        // dir is required, no extra args allowed
        throw new Zend_Console_Getopt_Exception('', $cmdLine->getUsageMessage());
    }
} catch(Zend_Console_Getopt_Exception $exception) {
    print $exception->getUsageMessage();
    exit(1);
}

// Get all computers, sorted by Client ID
$statement = Model_Computer::createStatementStatic(
    null,
    'ClientId'
);
while ($computer = $statement->fetchObject('Model_Computer')) {
    $id = $computer->getClientId();
    print "Exporting $id\n";
    try {
        // The Client ID is used for filename generation. Since database content
        // can't be trusted, it must be validated. This is tricky especially on
        // Windows. Instead, check for a strict NAME-YYYY-MM-DD-HH-MM-SS pattern
        // with NAME consisting of letters, digits, dashes and underscores. This
        // Pattern is safe to be used as a filename.
        if (!preg_match('/^[A-Za-z0-9_-]+-\d\d\d\d-\d\d-\d\d-\d\d-\d\d-\d\d$/', $id)) {
            throw new UnexpectedValueException($id . ' is not a valid filename part');
        }
        // Save content to file
        $document = $computer->toDomDocument();
        $filename = $cmdLine->dir . DIRECTORY_SEPARATOR . $document->getFilename();
        if (!$document->save($filename)) {
            throw new RuntimeException('Could not write file ' . $filename);
        }
        // Optional validation.
        if ($cmdLine->validate) {
            $document->forceValid();
        }
    } catch(Exception $exception) {
        print 'ERROR: ';
        print $exception->getMessage();
        print PHP_EOL;
        exit(1);
    }
}


