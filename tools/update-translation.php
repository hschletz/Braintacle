#!/usr/bin/php
<?php
/**
 * Update translation files (.pot, .po, .mo) if sources have changed
 *
 * $Id$
 *
 * Copyright (C) 2011,2012 Holger Schletz <holger.schletz@web.de>
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
/**
 * Adding new message strings is a two step process. Run this script to extract
 * new strings from all .php and .phtml source files. This will update all .po
 * files. After translating new strings in these files, run this script again
 * to compile the .mo files which will be used by the application.
 *
 * If you pass the --noextract option to this script, xgettext will not be invoked
 * and braintacle.pot will not be updated.
 *
 * Note that zend.pot and braintacle-library.pot are maintained manually. If the
 * application complains about untranslated messages that originate from library
 * code, add the message to the .pot file and run this script to update the .po files.
 */

error_reporting(-1);

// Define some constants for placeholder replacements
define(
    'TITLE',
<<<EOT
# Translation file for Braintacle
#
# \$Id\$
#
EOT
);

define(
    'COPYRIGHT',
<<<EOT
# Copyright (C) 2011,2012 Holger Schletz <holger.schletz@web.de>
#
# This program is free software; you can redistribute it and/or modify it
# under the terms of the GNU General Public License as published by the Free
# Software Foundation; either version 2 of the License, or (at your option)
# any later version.
#
# This program is distributed in the hope that it will be useful, but WITHOUT
# ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
# FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for
# more details.
#
# You should have received a copy of the GNU General Public License along with
# this program; if not, write to the Free Software Foundation, Inc.,
# 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
EOT
);

// All paths are relative to this script's parent directory
$basePath = realpath(dirname(dirname(__FILE__)));
$languagePath = realpath($basePath . '/languages');
$applicationPath = realpath($basePath . '/application');
$potFileName = realpath($languagePath . '/braintacle.pot');

// STAGE 1: Let xgettext extract all strings to $newPot
if (in_array('--noextract', $_SERVER['argv'])) {
    print "Skipping xgettext run\n";
} else {
    print "Running xgettext on source files... ";
    $cmd = array(
        'xgettext',
        '--directory=' . escapeshellarg($applicationPath),
        '--default-domain=braintacle',
        '--output=-',
        '--language=PHP',
        '--sort-by-file',
        '--package-name=braintacle',
        '--copyright-holder="Holger Schletz"',
        '--keyword=translate',
        '--keyword=setLabel',
        '--keyword=setLegend',
        '--keyword=setDescription',
        '--keyword=_setError',
        '--keyword=_setErrorHtml',
    );
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($applicationPath));
    foreach ($iterator as $file) {
        // Retrieve relative path only
        $file = $iterator->getSubPathName();

        // Ignore everything except *.php and *.phtml
        if (substr($file, -4) != '.php' and substr($file, -6) != '.phtml')
            continue;

        // append file to command line
        $cmd[] = $file;
    }
    $cmd = implode(' ', $cmd);
    exec($cmd, $newPot, $result);
    if ($result) {
        print "ERROR: xgettext returned with error code $result.\n";
        print "Command line was:\n\n";
        print "$cmd\n\n";
        exit(1);
    }
    print "done.\n";

    // Replace some placeholders
    foreach ($newPot as $index => $line) {
        switch ($line) {
        case '# SOME DESCRIPTIVE TITLE.':
            $newPot[$index] = TITLE;
            break;
        case '# Copyright (C) YEAR Holger Schletz':
            $newPot[$index] = COPYRIGHT;
            break;
        case '# This file is distributed under the same license as the PACKAGE package.':
            $newPot[$index] = '#';
            break;
        case '"Language-Team: LANGUAGE <LL@li.org>\n"':
            $newPot[$index] = '"Language-Team: LANGUAGE <EMAIL@ADDRESS>\n"';
            break;
        default:
            // Strip line numbers from comments. These shift too often on
            // totally unrelated changes on the source file.
            if (preg_match('/^#: /', $line)) {
                $line = preg_replace('/:\d+/', ';', $line); // replace with ';' for better readability
                $newPot[$index] = rtrim($line, ';'); // strip trailing semicolon
            }
        }
    }

    // Read existing braintacle.pot into $oldPot
    $oldPot = file($potFileName, FILE_IGNORE_NEW_LINES);
    if ($oldPot == false) {
        print "ERROR: could not read $potFileName\n";
        exit(1);
    }

    // Compare $oldPot with $newPot, write file only if significant changes are detected
    // See http://php.net/manual/en/function.array-diff.php#82143 for an explanation
    $union = array_merge($oldPot, $newPot);
    $intersect = array_intersect($oldPot, $newPot);
    $diff = array_diff($union, $intersect);
    foreach (array_keys($diff) as $index) {
        if (strpos($diff[$index], '"POT-Creation-Date:') === 0
            or strpos($diff[$index], '#') === 0) {
            unset($diff[$index]);
        }
    }
    if (count($diff) or in_array('--force', $_SERVER['argv'])) {
        $potFile = fopen($potFileName, 'w');
        if (!$potFile) {
            print "ERROR: could not open $potFileName for writing.\n";
            exit(1);
        }
        foreach ($newPot as $line) {
            if (fwrite($potFile, $line . "\n") === false) {
                print "ERROR: writing to $potFileName aborted.\n";
                exit(1);
            }
        }
        print "Changes written to $potFileName.\n";
    } else {
        print "No changes detected.\n";
    }
}

// STAGE 2: Update .po files if necessary and compile them to .mo files.
print 'Updating and compiling .po files... ';
$baseNames = array(
    'braintacle',
    'braintacle-library',
    'zend',
);
$iterator = new DirectoryIterator($languagePath);
foreach ($iterator as $entry) {
    $entry = $iterator->getFileName();
    if ($iterator->isDir() and substr($entry, 0, 1) != '.') {
        foreach ($baseNames as $baseName) {
            $potFileName = realpath("$languagePath/$baseName.pot");
            $poFileName  = realpath("$languagePath/$entry/$baseName.po");
            if (empty($poFileName)) {
                print "WARNING: missing file $languagePath/$entry/$baseName.po\n";
                continue;
            }
            // Update .po file.
            $cmd = array(
                'msgmerge',
                '--quiet',
                '--update',
                '--backup=off',
                '--sort-by-file',
                escapeshellarg($poFileName),
                escapeshellarg($potFileName),
            );
            $cmd = implode(' ', $cmd);
            exec($cmd, $output, $result);
            if ($result) {
                print "ERROR: msgmerge returned with error code $result.\n";
                print "Command line was:\n\n";
                print "$cmd\n\n";
                exit(1);
            }

            // Compile .mo file.
            $moPath = "$languagePath/$entry/LC_MESSAGES";
            @mkdir($moPath);
            $moPath = realpath($moPath);
            if (!$moPath) {
                print "ERROR: Could not create LC_MESSAGES directory.\n";
                exit(1);
            }
            $cmd = array(
                'msgfmt',
                '-o',
                escapeshellarg($moPath . DIRECTORY_SEPARATOR . "$baseName.mo"),
                escapeshellarg($poFileName),
            );
            $cmd = implode(' ', $cmd);
            exec($cmd, $output, $result);
            if ($result) {
                print "ERROR: msgfmt returned with error code $result.\n";
                print "Command line was:\n\n";
                print "$cmd\n\n";
                exit(1);
            }
        }
    }
}
print "done\n";
exit(0);
