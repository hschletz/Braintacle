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
            array('en', 'default', "File '%value%' is not readable", "File '%value%' is not readable"),
            array('en_UK', 'default', "File '%value%' is not readable", "File '%value%' is not readable"),
            array('de', 'default', "File '%value%' is not readable", "Datei '%value%' ist nicht lesbar"),
            array('de_DE', 'default', "File '%value%' is not readable", "Datei '%value%' ist nicht lesbar"),
            // Messages from ZF resources
            array('en', 'Zend', "Value is required and can't be empty", "Value is required and can't be empty"),
            array('en_UK', 'Zend', "Value is required and can't be empty", "Value is required and can't be empty"),
            array('de', 'Zend', "Value is required and can't be empty", 'Es wird eine Eingabe benötigt'),
            array('de_DE', 'Zend', "Value is required and can't be empty", 'Es wird eine Eingabe benötigt'),
        );
    }

    /**
     * @dataProvider translatorSetupProvider
     */
    public function testTranslatorSetup($locale, $textDomain, $message, $expectedMessage)
    {
        \Locale::setDefault($locale);
        $application = \Zend\Mvc\Application::init(
            array(
                'modules' => array('Library'),
                'module_listener_options' => array(
                    'module_paths' => array(
                        'Library' => \Library\Application::getPath('module/Library')
                    ),
                ),
            )
        );
        $translator = $application->getServiceManager()->get('MvcTranslator');
        $this->assertEquals($expectedMessage, $translator->translate($message, $textDomain));
    }

    public function missingTranslationForDevelopmentProvider()
    {
        return array(
            array('development', 'de'),
            array('development', 'de_DE'),
            array('test', 'de'),
            array('test', 'de_DE'),
        );
    }

    /**
     * @dataProvider missingTranslationForDevelopmentProvider
     */
    public function testMissingTranslationTriggersNoticeInDevelopmentOrTestMode($mode, $locale)
    {
        $this->setExpectedException(
            'PHPUnit_Framework_Error_Notice',
            'Missing translation: this_string_is_not_translated'
        );
        putenv('APPLICATION_ENV=' . $mode);
        \Locale::setDefault($locale);
        $application = \Zend\Mvc\Application::init(
            array(
                'modules' => array('Library'),
                'module_listener_options' => array(
                    'module_paths' => array(
                        'Library' => \Library\Application::getPath('module/Library')
                    ),
                ),
            )
        );
        $translator = $application->getServiceManager()->get('MvcTranslator');
        $this->assertEquals('this_string_is_not_translated', $translator->translate('this_string_is_not_translated'));
    }

    public function missingTranslationWithoutEnvironmentProvider()
    {
        return array(
            array('de'),
            array('de_DE')
        );
    }

    /**
     * @dataProvider missingTranslationWithoutEnvironmentProvider
     */
    public function testMissingTranslationDoesNotTriggerNoticeInProductionMode($locale)
    {
        putenv('APPLICATION_ENV=production');
        \Locale::setDefault($locale);
        $application = \Zend\Mvc\Application::init(
            array(
                'modules' => array('Library'),
                'module_listener_options' => array(
                    'module_paths' => array(
                        'Library' => \Library\Application::getPath('module/Library')
                    ),
                ),
            )
        );
        $translator = $application->getServiceManager()->get('MvcTranslator');
        $this->assertEquals('this_string_is_not_translated', $translator->translate('this_string_is_not_translated'));
    }

    /**
     * @dataProvider missingTranslationForDevelopmentProvider
     */
    public function testMissingTranslationDoesNotTriggerNoticeForZendResources($mode, $locale)
    {
        putenv('APPLICATION_ENV=' . $mode);
        \Locale::setDefault($locale);
        $application = \Zend\Mvc\Application::init(
            array(
                'modules' => array('Library'),
                'module_listener_options' => array(
                    'module_paths' => array(
                        'Library' => \Library\Application::getPath('module/Library')
                    ),
                ),
            )
        );
        $translator = $application->getServiceManager()->get('MvcTranslator');
        $this->assertEquals(
            'this_string_is_not_translated',
            $translator->translate('this_string_is_not_translated', 'Zend')
        );
    }
}
