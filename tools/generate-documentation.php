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

use \Zend\Dom\Document\Query as Query;

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
require_once "$basePath/module/Library/Application.php";
\Library\Application::init('Cli');

// Autoload namespaces from which classes are referenced
require_once("$basePath/module/Console/Console/Module.php");
$module = new \Console\Module;
\Zend\Loader\AutoloaderFactory::factory($module->getAutoloaderConfig());
require_once("$basePath/module/Protocol/Module.php");
$module = new \Protocol\Module;
\Zend\Loader\AutoloaderFactory::factory($module->getAutoloaderConfig());

// Determine phpDocumentor invocation method
if (isset($_SERVER['argv'][1])) {
    $phpDocCmd = 'php ' . realpath($_SERVER['argv'][1] . '/bin/phpdoc.php');
} else {
    $phpDocCmd = 'phpdoc';
}

// Invoke phpDocumentor.
$cmd = array(
    $phpDocCmd,
    'run',
    '--progressbar',
    '--config',
    realpath("$basePath/doc/api/phpdoc.xml"),
    '--sourcecode',
    '-m TODO',
);
$cmd = implode(' ', $cmd);
system($cmd, $result);
if ($result) {
    print "ERROR: phpDocumentor returned with error code $result.\n";
    print "Command line was:\n\n";
    print "$cmd\n\n";
    exit(1);
}

// Postprocess output to work around several phpDocumentor bugs:
// - Arguments from inherited method docblocks are reported as missing, even if
//   they are documented in a parent's docblock and properly shown in the
//   documentation.
// - Errors are only output to the console when a file is parsed, not when it is
//   processed from the cache.
print "Postprocessing output\n";
$errorFile = "$basePath/doc/api/reports/errors.html";
$errorDocument = new \Zend\Dom\Document(file_get_contents($errorFile));
if ($errorDocument->getErrors()) {
    print "Error parsing output.\n";
    exit(1);
}
// Extract messages from error tables
$messagesToRemove = array();
foreach (Query::execute('//div[@class="package-contents"]/a', $errorDocument) as $node) { // skip empty containers
    $baseNode = $node->parentNode; // Will be removed if no errors remain

    $fileName = $node->getAttribute('name'); // file which contains the error
    $fileDocument = new \Zend\Dom\Document(
        file_get_contents($basePath . '/doc/api/files/' . basename(strtr($fileName, '/', '.'), '.php') . '.html')
    );

    // Extract hyperlink to class file
    $link = Query::execute('//h2[text()="Classes"]/following-sibling::table/tr/td/a', $fileDocument);

    // Extract namespaced class from class file name
    $class = strtr(basename($link[0]->getAttribute('href'), '.html'), '.', '\\');

    // Determine parent class and implemented interfaces from which documentation may be inherited.
    $reflectionClass = new \ReflectionClass($class);
    $parentClass = $reflectionClass->getParentClass();
    $interfaces = $reflectionClass->getInterfaces();

    // Iterate over messages for current file/class.
    $rowsToRemove = array();
    foreach (Query::execute($baseNode->getNodePath() . '/div/table/tbody/tr', $errorDocument) as $row) {
        $ignoreError = false;
        $message = $row->childNodes->item(4)->nodeValue;
        if (preg_match('/^Argument .* is missing from the Docblock of (.*)\(\)$/', $message, $matches)) {
            $method = $matches[1];
            if ($parentClass) {
                $ignoreError = $parentClass->hasMethod($method);
            }
            if (!$ignoreError) {
                foreach ($interfaces as $interface) {
                    if ($interface->hasMethod($method)) {
                        $ignoreError = true;
                        break;
                    }
                }
            }
        } elseif ($message == 'Only one @package tag is allowed') {
            $ignoreError = true; // TODO remove workaround when no @package tags are used anymore
        }

        if ($ignoreError) {
            $rowsToRemove[] = $row;
        } else {
            // Print message to console - phpdoc only does this for uncached files.
            printf(
                "%s (%s:%d): %s\n",
                strtoupper($row->childNodes->item(0)->nodeValue), // severity
                $fileName,
                $row->childNodes->item(2)->nodeValue, // line
                $message
            );
        }
    }
    if ($rowsToRemove) {
        $messagesToRemove[$fileName]['baseNode'] = $baseNode;
        $messagesToRemove[$fileName]['rows'] = $rowsToRemove;
    }
}

// Remove incorrect messages from the error document
$messagesRemoved = 0;
foreach ($messagesToRemove as $fileName => $messages) {
    // Remove table row containing message
    $tbody = $messages['rows'][0]->parentNode; // identical for all rows belonging to current file
    foreach ($messages['rows'] as $row) {
        $tbody->removeChild($row);
        $messagesRemoved++;
    }
    // If no more rows are left, remove entire file block and navigation link.
    if ($tbody->childNodes->length == 0) {
        // The original template keeps 1 block per file, even if it's empty. It
        // serves no purpose, and removing it entirely is simpler.
        $messages['baseNode']->parentNode->removeChild($messages['baseNode']);
        // Remove navigation link
        $item = Query::execute("//li/a[@href='#$fileName']/..", $errorDocument);
        $item[0]->parentNode->removeChild($item[0]);
    }
}

// Overwrite error file.
$errorDocument->getDomDocument()->saveHTMLFile($errorFile);
unset($errorDocument);

// Adjust error counter in all output files.
foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator("$basePath/doc/api")) as $file) {
    if ($file->getExtension() == 'html') {
        $document = new \Zend\Dom\Document(file_get_contents($file->getPathname()));
        $counter = Query::execute(
            '//li[@id="reports-menu"]//a[@href="../reports/errors.html" or @href="reports/errors.html"]/span',
            $document
        );
        $counter = $counter[0];
        $counter->nodeValue -= $messagesRemoved;
        $document->getDomDocument()->saveHTMLFile($file->getPathname());
    }
}
