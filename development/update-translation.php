#!/usr/bin/php
<?php

/**
 * Update translation files (.pot, .po) if sources have changed
 *
 * Copyright (C) 2011-2022 Holger Schletz <holger.schletz@web.de>
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

use Gettext\Generator\PoGenerator;
use Gettext\Scanner\PhpScanner;
use Gettext\Translation;
use Gettext\Translations;
use Latte\Engine;
use Latte\Token;
use Library\Application;
use Library\FileObject;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

// Run this script to extract new strings from all PHP source files to .pot
// files and update corresponding .po files. Some .pot files are maintained
// manually. For these modules only the .po files are updated.

error_reporting(-1);

require_once __DIR__ . '/../vendor/autoload.php';

// Module configuration
//
// The "translationPath" element must be present for each module. If the
// "subdirs" element is present, message strings are extracted from these
// subdirectories. The "keywords" element lists function names whose first
// argument is to be extracted.
$modules = array(
    'Console' => array(
        'subdirs' => array('Controller', 'Form', 'Navigation', 'View/Helper', 'views'),
        'keywords' => ['_', 'translate', 'setLabel', 'setMessage'],
        'translationPath' => 'data/i18n',
    ),
    'Library' => array(
        'keywords' => ['_'],
        'translationPath' => 'data/i18n',
    ),
);

foreach ($modules as $module => $config) {
    $modulePath = Application::getPath("module/$module");
    $translationPath = Application::getPath("module/$module/$config[translationPath]");
    $potFileName = Application::getPath("module/$module/$config[translationPath]/$module.pot");

    if (isset($config['subdirs'])) {
        // STAGE 1: extract all strings from module
        print "Extracting strings fron $module module...";

        $translations = Translations::create();
        $translations->setDescription("Translation file for $module module");
        $translations->getHeaders()->set('Content-Type', 'text/plain; charset=UTF-8');
        $translations = $translations->mergeWith(
            parsePhpFiles($modulePath, $config['subdirs'], $config['keywords'])
        );
        if ($module == 'Console') {
            $translations = $translations->mergeWith(parseTemplates());
        }

        $generator = new PoGenerator();
        $newPot = $generator->generateString($translations);

        print " done.\n";

        if (in_array('--force', $_SERVER['argv'])) {
            $update = true;
        } else {
            $oldPot = FileObject::fileGetContents($potFileName);
            $update = ($newPot != $oldPot);
        }
        if ($update) {
            $fileSystem = new Filesystem();
            $fileSystem->dumpFile($potFileName, $newPot);
            print "Changes written to $potFileName.\n";
        } else {
            print "No changes detected for $potFileName.\n";
        }
    }

    // STAGE 2: Update .po files if necessary
    print "Updating .po files for $module module...";
    foreach (new \GlobIterator("$translationPath/*.po", \GlobIterator::CURRENT_AS_PATHNAME) as $poFileName) {
        $cmd = [
            'msgmerge',
            '--quiet',
            '--update',
            '--backup=off',
            '--sort-by-file',
            $poFileName,
            $potFileName,
        ];
        $process = new \Symfony\Component\Process\Process($cmd);
        if ($process->run()) {
            printf("ERROR: msgmerge returned with error code %d.\n", $process->getExitCode());
            print "Command line was:\n\n";
            print $process->getCommandLine();
            print "\n\n";
            exit(1);
        }
    }
    print " done.\n";
}

/**
 * Extract strings from all PHP scripts in given directories.
 */
function parsePhpFiles(string $baseDir, array $subDirs, array $keywords): Translations
{
    $functions = [];
    foreach ($keywords as $keyword) {
        $functions[$keyword] = 'gettext';
    }
    $translations = Translations::create();

    chdir($baseDir);
    $files = [];
    foreach ($subDirs as $subDir) {
        $directoryIterator = new RecursiveDirectoryIterator(
            $subDir,
            RecursiveDirectoryIterator::CURRENT_AS_PATHNAME
        );
        $iterator = new RecursiveIteratorIterator($directoryIterator);
        foreach ($iterator as $file) {
            if (substr($file, -4) == '.php') {
                $files[] = $file;
            }
        }
    }
    sort($files);
    foreach ($files as $file) {
        $fileTranslations = parsePhpFile($file, $functions);
        $translations = $translations->mergeWith($fileTranslations);
    }
    return $translations;
}

/**
 * Extract strings from a PHP script.
 */
function parsePhpFile(string $file, array $functions): Translations
{
    $scanner = new PhpScanner(Translations::create());
    $scanner->setFunctions($functions);
    $scanner->ignoreInvalidFunctions(); // Required for function calls with non-literal arguments
    $scanner->scanFile($file);

    $translations = Translations::create();
    /** @var Translation */
    foreach ($scanner->getTranslations()[''] as $scannedTranslation) {
        // Remove line numbers which would generate too much noise. Because they
        // cannot be removed from a Translation object, all relevant data is
        // copied to a new instance.
        $translation = Translation::create(null, $scannedTranslation->getOriginal());
        $translation->getReferences()->add($file);

        // Some strings are misdetected as format strings, like validation
        // message templates with placeholders (%placeholder%). Try to use
        // the string as a format string and remove the flag on error.
        $flags = $scannedTranslation->getFlags();
        if ($flags->has('php-format')) {
            $template = $scannedTranslation->getOriginal();
            // Generate array with enough elements (1) which will be valid
            // arguments for %d and %s. Simply counting does not account for
            // escaped %%, but that's not a problem for now.
            $numPlaceholders = substr_count($template, '%');
            $args = array_fill(0, $numPlaceholders, 1);
            try {
                vsprintf($template, $args);
            } catch (ValueError) {
                $flags->delete('php-format');
            }
            $translation->getFlags()->add(...$flags->toArray());
        }

        $translations->add($translation);
    }
    return $translations;
}

/**
 * Parse all Latte templates.
 */
function parseTemplates(): Translations
{
    $templatePath = Application::getPath('templates');

    // Construct list of template files, ordered by relative path.
    $templates = [];
    foreach (
        new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $templatePath,
                RecursiveDirectoryIterator::CURRENT_AS_PATHNAME | RecursiveDirectoryIterator::SKIP_DOTS
            )
        ) as $path
    ) {
        $file = Path::makeRelative($path, $templatePath);
        $templates[$file] = $path;
    }
    ksort($templates);

    // Append extracted strings from each template.
    $translations = Translations::create();
    foreach ($templates as $file => $path) {
        $translations = $translations->mergeWith(parseTemplate($path, $file));
    }
    return $translations;
}

/**
 * Parse single Latte template.
 */
function parseTemplate(string $file, string $relativePath): Translations
{
    $template = file_get_contents($file);
    if ($template === false) {
        throw new RuntimeException('Error reading ' . $file);
    }

    $translations = Translations::create();

    // Invoke Latte parser, assuming HTML content.
    $engine = new Engine();
    $parser = $engine->getParser();
    $parser->setContentType(Engine::CONTENT_HTML);
    $tokens = $parser->parse($template);
    foreach ($tokens as $token) {
        if ($token->type != Token::MACRO_TAG) {
            continue;
        }
        if ($token->name == '=') {
            $message = parseSimpleTemplateExpression($token->value);
        } else {
            $message = parseComplexTemplateExpression($token->value);
        }
        if (!$message) {
            continue;
        }

        $translation = Translation::create(null, $message);
        $translation->getReferences()->add($relativePath);
        $translations->add($translation);
    }

    return $translations;
}

/**
 * Parse simple Latte expression.
 */
function parseSimpleTemplateExpression(string $expression): ?string
{
    // Thanks to Latte's PHP-like Syntax, use PHP's tokenizer for further
    // analysis.
    $tokens = PhpToken::tokenize('<?php ' . $expression);
    if ($tokens[1] != 'translate') {
        return null;
    }
    // Found translate(), extract first argument.
    $token = $tokens[3];
    if (!$token->is(T_CONSTANT_ENCAPSED_STRING)) {
        throw new RuntimeException('Unexpected token: ' . $token->text);
    }
    // Use eval() to reliably remove and unescape quotes. This is safe because
    // the token is guaranteed to be a string literal.
    return eval("return {$token->text};");
}

/**
 * Parse complex expression with extended Latte syntax.
 */
function parseComplexTemplateExpression(string $expression): ?string
{
    // Fall back to dumb regex parser and hope for the best.
    return preg_match("/translate\('(.+?)'\)/", $expression, $matches) ? $matches[1] : null;
}
