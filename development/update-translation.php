#!/usr/bin/php
<?php
/**
 * Update translation files (.pot, .po) if sources have changed
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

// Run this script to extract new strings from all PHP source files to .pot
// files and update corresponding .po files. Some .pot files are maintained
// manually. For these modules only the .po files are updated.

error_reporting(-1);
require_once __DIR__ . '/../module/Library/FileObject.php';

// Module configuration
//
// The "translationPath" element must be present for each module. If the
// "subdirs" element is present, message strings are extracted from these
// subdirectories. The "keywords" element lists function names that are used as
// xgettext's --keyword option. The "_" function is always evaluated and not
// explicitly listed.
$modules = array(
    'Console' => array(
        'subdirs' => array('Console/Controller', 'Console/Form', 'Console/Navigation', 'Console/View/Helper', 'view'),
        'keywords' => array('translate', 'setLabel', 'setMessage', 'addSuccessMessage', 'addErrorMessage'),
        'translationPath' => 'data/i18n',
    ),
    'Library' => array(
        'translationPath' => 'data/i18n',
    ),
);

$template = <<<EOT
# Translation file for %s module
#
# Copyright (C) 2011-2015 Holger Schletz <holger.schletz@web.de>
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
#
msgid ""
msgstr ""
"Content-Type: text/plain; charset=UTF-8\\n"

%s\n
EOT;

// All paths are relative to this script's parent directory
$basePath = dirname(__DIR__);

foreach ($modules as $module => $config) {
    // Use DIRECTORY_SEPARATOR for paths that are used as shell arguments
    $modulePath = $basePath . DIRECTORY_SEPARATOR . 'module' . DIRECTORY_SEPARATOR . $module;
    $translationPath = $modulePath . DIRECTORY_SEPARATOR . $config['translationPath'];
    $potFileName = $translationPath . DIRECTORY_SEPARATOR . "$module.pot";
    if (isset($config['subdirs'])) {
        // STAGE 1: Let xgettext extract all strings from module to $newPot
        print "Extracting strings fron $module module...";
        $cmd = array(
            'xgettext',
            '--directory=' . escapeshellarg($modulePath),
            '--output=-',
            '--language=PHP',
            '--omit-header',
            '--sort-by-file',
            '--add-location=file',
        );
        foreach ($config['keywords'] as $keyword) {
            $cmd[] = "--keyword=$keyword";
        }
        foreach ($config['subdirs'] as $subdir) {
            foreach (
                new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator(
                        "$modulePath/$subdir",
                        \RecursiveDirectoryIterator::CURRENT_AS_SELF
                    )
                ) as $file
            ) {
                $file = $file->getSubPathName();
                if (substr($file, -4) == '.php') {
                    $cmd[] = escapeshellarg($subdir . DIRECTORY_SEPARATOR . $file);
                }
            }
        }
        $cmd = implode(' ', $cmd);
        exec($cmd, $newPot, $result);
        if ($result) {
            print "ERROR: xgettext returned with error code $result.\n";
            print "Command line was:\n\n";
            print "$cmd\n\n";
            exit(1);
        }
        print " done.\n";

        if (in_array('--force', $_SERVER['argv'])) {
            $update = true;
        } else {
            // Read existing POT file into $oldPot
            $oldPot = \Library\FileObject::fileGetContentsAsArray($potFileName, FILE_IGNORE_NEW_LINES);
            // Skip to first message string (strip header and first empty line)
            $startPos = array_search('', $oldPot, true);
            if ($startPos === false) {
                print "WARNING: File $potFileName as unexpected content. Skipping.\n";
                continue;
            }
            $oldPot = array_slice($oldPot, $startPos + 1);
            $update = ($newPot != $oldPot);
        }
        if ($update) {
            \Library\FileObject::FilePutContents(
                $potFileName,
                sprintf($template, $module, implode("\n", $newPot))
            );
            print "Changes written to $potFileName.\n";
        } else {
            print "No changes detected for $potFileName.\n";
        }
    }

    // STAGE 2: Update .po files if necessary
    print "Updating .po files for $module module...";
    foreach (new \GlobIterator("$translationPath/*.po", \GlobIterator::CURRENT_AS_PATHNAME) as $poFileName) {
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
    }
    print " done.\n";
}
