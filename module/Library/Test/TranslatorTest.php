<?php
/**
 * Tests for the Translator setup
 *
 * Copyright (C) 2011-2016 Holger Schletz <holger.schletz@web.de>
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

namespace Library\Test;

/**
 * Tests for the Translator setup
 */
class TranslatorTest extends \PHPUnit_Framework_TestCase
{
    protected static $_defaultLocale;
    protected static $_applicationEnvironment;

    public static function setUpBeforeClass()
    {
        // Preserve global state
        static::$_defaultLocale = \Locale::getDefault();
        static::$_applicationEnvironment = \Library\Application::getEnvironment();
    }

    public function tearDown()
    {
        // Reset after every test to avoid interference
        \Locale::setDefault(static::$_defaultLocale);
        putenv('APPLICATION_ENV=' . static::$_applicationEnvironment);
    }

    public function translatorSetupProvider()
    {
        return array(
            // Messages from Library module
            array('en', "File '%value%' is not readable", "File '%value%' is not readable"),
            array('en_UK', "File '%value%' is not readable", "File '%value%' is not readable"),
            array('de', "File '%value%' is not readable", "Datei '%value%' ist nicht lesbar"),
            array('de_DE', "File '%value%' is not readable", "Datei '%value%' ist nicht lesbar"),
            // Messages from ZF resources
            array('en', "Value is required and can't be empty", "Value is required and can't be empty"),
            array('en_UK', "Value is required and can't be empty", "Value is required and can't be empty"),
            array('de', "Value is required and can't be empty", 'Es wird eine Eingabe benötigt'),
            array('de_DE', "Value is required and can't be empty", 'Es wird eine Eingabe benötigt'),
        );
    }

    /**
     * @dataProvider translatorSetupProvider
     */
    public function testTranslatorSetup($locale, $message, $expectedMessage)
    {
        \Locale::setDefault($locale);
        $application = \Library\Application::init('Library', true);
        $translator = $application->getServiceManager()->get('MvcTranslator');
        $this->assertEquals($expectedMessage, $translator->translate($message));
    }

    public function missingTranslationProvider()
    {
        return array(
            array('de'),
            array('de_DE'),
        );
    }

    /**
     * @dataProvider missingTranslationProvider
     */
    public function testMissingTranslationTriggersNoticeInDevelopmentMode($locale)
    {
        $this->setExpectedException(
            'PHPUnit_Framework_Error_Notice',
            'Missing translation: this_string_is_not_translated'
        );
        \Locale::setDefault($locale);
        $application = \Library\Application::init('Library', true);
        $translator = $application->getServiceManager()->get('MvcTranslator');
        $this->assertEquals('this_string_is_not_translated', $translator->translate('this_string_is_not_translated'));
    }

    /**
     * @dataProvider missingTranslationProvider
     */
    public function testMissingTranslationDoesNotTriggerNoticeInProductionMode($locale)
    {
        putenv('APPLICATION_ENV=production');
        \Locale::setDefault($locale);
        $application = \Library\Application::init('Library', true);
        $translator = $application->getServiceManager()->get('MvcTranslator');
        $this->assertEquals('this_string_is_not_translated', $translator->translate('this_string_is_not_translated'));
    }
}
