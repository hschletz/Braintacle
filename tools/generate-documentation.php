#!/usr/bin/php
<?php
/**
 * Generate/update API documentation.
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
 */

/*
 * USAGE: generate-documentation.php [phpDocumentor2 path]
 *
 * If no argument is given, phpDocumentor is invoked via the 'phpdoc' command.
 * If this is not available or a different version (a development snapshot, for
 * example) should be used, specify the path to the phpDocumentor installation.
 */

error_reporting(-1);

/**
 * Retrieve class node belonging to an error node
 * @param DOMElement $errorElement <error> element
 * @return DOMElement Class element or NULL
 **/
function getClassFromError($errorElement)
{
    // Errors are stored per file. Determine file.
    $file = $errorElement->parentNode->parentNode;
    // Assuming 1 class per file, get class
    $fileClasses = $file->getElementsByTagName('class');
    if ($fileClasses->length) {
        return $fileClasses->item(0);
    } else {
        return null;
    }
}

/**
 * Determine presence of 'param' tag - either direct or inherited.
 * @param DOMElement $class 'class' element to search in
 * @param string $method Method name
 * @param string $argument Argument name
 * @return bool
 */
function hasTag($class, $method, $argument)
{
    global $xPath, $classes;

    // Iterate all class methods until $method is found
    foreach ($xPath->query('method/name', $class) as $methodName) {
        if ($methodName->nodeValue != $method) {
            continue;
        }
        // Method found. Check for matching tag.
        if ($xPath->query("docblock/tag[@variable='$argument']", $methodName->parentNode)->length) {
            return true;
        }
    }

    // Tag not present in this class. Search for parent.
    $parentClass = $class->getElementsByTagName('extends');
    if (!$parentClass->length) {
        return false; // Class has no parent, ergo no tag.
    }

    // Class has parent.
    $parentClassName = $parentClass->item(0)->nodeValue;
    if (!isset($classes[$parentClassName])) {
        return false; // Parent class unknown (external class). No documentation available.
    }

    // Parent documentation available. Lookup tag there.
    return hasTag($classes[$parentClassName], $method, $argument);
}

/**
 * Work around typehint misdetection
 * @param DOMElement $class 'class' element to search in
 * @param string $method Method name
 * @param string $argument Argument name
 * @return bool
 */
function typeHintCorrect($class, $method, $argument)
{
    global $xPath;

    // Iterate all class methods until $method is found
    foreach ($xPath->query('method/name', $class) as $methodName) {
        if ($methodName->nodeValue != $method) {
            continue;
        }
        // Get typehint from <tag> element.
        $tag = $xPath->query("docblock/tag[@variable='$argument']", $methodName->parentNode)->item(0);
        $typeFromTag = $tag->getAttribute('type');

        // Get typehint from <argument> element
        foreach ($xPath->query("argument/name", $methodName->parentNode) as $argumentName) {
            if ($argumentName->nodeValue != $argument) {
                continue;
            }
            $typeFromArgument = $xPath->query('type', $argumentName->parentNode)->item(0)->nodeValue;

            // Compare types, using prefix
            return $typeFromTag == 'array' and $typeFromArgument == '\\array';
        }
    }
}


// All paths are relative to this script's parent directory
$basePath = realpath(dirname(dirname(__FILE__)));

// Determine phpDocumentor invocation method
if (isset($_SERVER['argv'][1])) {
    $phpDocCmd = 'php ' . realpath($_SERVER['argv'][1] . '/bin/phpdoc.php');
} else {
    $phpDocCmd = 'phpdoc';
}

print "Running phpDocumentor on source files\n";

// STAGE 1: Parse source files and generate structure file.
$cmd = array(
    $phpDocCmd,
    'project:parse',
    '--directory',
    implode(
        ',',
        array(
            realpath("$basePath/application/models"),
            realpath("$basePath/application/forms"),
            realpath("$basePath/application/views/helpers"),
            realpath("$basePath/application/controllers/helpers"),
            realpath("$basePath/library/Braintacle"),
        )
    ),
    '--target',
    realpath("$basePath/doc/api"),
    '--extensions',
    'php',
    '--title',
    '"Braintacle API documentation"',
    '--progressbar',
);
$cmd = implode(' ', $cmd);
system($cmd, $result);
if ($result) {
    print "ERROR: phpDocumentor returned with error code $result.\n";
    print "Command line was:\n\n";
    print "$cmd\n\n";
    exit(1);
}

// STAGE 2: remove non-errors from structure file.
$structure = new DomDocument('1.0', 'utf-8');
$structure->load(realpath("$basePath/doc/api/structure.xml"));
$xPath = new DOMXPath($structure);
$errorsToRemove = array();
$regexInheritedArgument = '#^Argument (.*) is missing from the Docblock of (.*)\(\)$#';
$regexTypehint = '#^The type hint of the argument is incorrect for the type definition ' .
                 'of the @param tag with argument (\\$[a-zA-Z0-9]+) in ([a-zA-Z_]+)\(\)$#';

// Generate list of all classes (prefixed with backslash)
foreach ($structure->getElementsByTagName('class') as $node) {
    $classes['\\' . $node->getElementsByTagName('name')->item(0)->nodeValue] = $node;
}

// Iterate over all error messages and add non-errors to list
foreach ($structure->getElementsByTagName('error') as $error) {
    $message = $error->nodeValue;
    if (preg_match($regexInheritedArgument, $message, $matches)) {
        // phpDocumentor2 incorrectly complains about "missing" arguments passed in via @inheritdoc
        $argument = $matches[1];
        $method = $matches[2];
        $class = getClassFromError($error);
        if ($class and hasTag($class, $method, $argument)) {
            $errorsToRemove[] = $error;
        }
    } elseif (preg_match($regexTypehint, $message, $matches)) {
        // phpDocumentor2 handles typehints from the global namespace incorrectly
        $argument = $matches[1];
        $method = $matches[2];
        $class = getClassFromError($error);
        if ($class and typeHintCorrect($class, $method, $argument)) {
            $errorsToRemove[] = $error;
        }
    }
}

// Remove errors from document.
foreach ($errorsToRemove as $error) {
    $parseMarker = $error->parentNode;
    $parseMarker->removeChild($error);
    // If no more messages are left, remove element
    if ($parseMarker->getElementsByTagName('*')->length == 0) {
        $parseMarker->parentNode->removeChild($parseMarker);
    }
}

// Overwrite structure file
$structure->save(realpath("$basePath/doc/api/structure.xml"));

// STAGE 3: Transform structure file into documentation
$cmd = array(
    $phpDocCmd,
    'project:transform',
    '--config',
    realpath("$basePath/doc/api/phpdoc.xml"),
    '--source',
    realpath("$basePath/doc/api/structure.xml"),
    '--target',
    realpath("$basePath/doc/api"),
);
$cmd = implode(' ', $cmd);
system($cmd, $result);
if ($result) {
    print "ERROR: phpDocumentor returned with error code $result.\n";
    print "Command line was:\n\n";
    print "$cmd\n\n";
    exit(1);
}

// STAGE 4: Print errors
foreach ($structure->getElementsByTagName('parse_markers') as $parseMarker) {
    foreach ($parseMarker->getElementsByTagName('*') as $issue) {
        $severity = strtoupper($issue->tagName);
        $file = $issue->parentNode->parentNode->getAttribute('path');
        $line = $issue->getAttribute('line');
        $message = $issue->nodeValue;
        print "$severity: $file:$line $message\n";
    }
}
