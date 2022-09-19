<?php

/**
 * Tests for the Translator setup
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

namespace Library\Test;

/**
 * Tests for the Translator setup
 */
class TranslatorTest extends \PHPUnit\Framework\TestCase
{
    protected static $_defaultLocale;

    public static function setUpBeforeClass(): void
    {
        // Preserve global state
        static::$_defaultLocale = \Locale::getDefault();
    }

    public function tearDown(): void
    {
        // Reset after every test to avoid interference
        \Locale::setDefault(static::$_defaultLocale);
    }

    public function translatorSetupProvider()
    {
        return array(
            // Messages from Library module
            array('en', "File '%value%' is not readable", "File '%value%' is not readable"),
            array('en_UK', "File '%value%' is not readable", "File '%value%' is not readable"),
            array('de', "File '%value%' is not readable", "Datei '%value%' ist nicht lesbar"),
            array('de_DE', "File '%value%' is not readable", "Datei '%value%' ist nicht lesbar"),
            // Messages from Laminas resources
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
        $serviceManager = \Library\Application::init('Library')->getServiceManager();
        $serviceManager->setService('Library\UserConfig', array());
        $translator = $serviceManager->get('MvcTranslator');
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
    public function testMissingTranslationTriggersNoticeWhenEnabled($locale)
    {
        $this->expectNotice();
        $this->expectNoticeMessage('Missing translation: this_string_is_not_translated');
        \Locale::setDefault($locale);
        $serviceManager = \Library\Application::init('Library')->getServiceManager();
        $serviceManager->setService(
            'Library\UserConfig',
            array(
                'debug' => array('report missing translations' => true),
            )
        );
        $translator = $serviceManager->get('MvcTranslator');
        $this->assertEquals('this_string_is_not_translated', $translator->translate('this_string_is_not_translated'));
    }

    /**
     * @dataProvider missingTranslationProvider
     */
    public function testMissingTranslationDoesNotTriggerNoticeWhenDisabled($locale)
    {
        \Locale::setDefault($locale);
        $serviceManager = \Library\Application::init('Library')->getServiceManager();
        $serviceManager->setService(
            'Library\UserConfig',
            array(
                'debug' => array('report missing translations' => false),
            )
        );
        $translator = $serviceManager->get('MvcTranslator');
        $this->assertEquals('this_string_is_not_translated', $translator->translate('this_string_is_not_translated'));
    }

    /**
     * @dataProvider missingTranslationProvider
     */
    public function testMissingTranslationDoesNotTriggerNoticebyDefault($locale)
    {
        \Locale::setDefault($locale);
        $serviceManager = \Library\Application::init('Library')->getServiceManager();
        $serviceManager->setService('Library\UserConfig', array());
        $translator = $serviceManager->get('MvcTranslator');
        $this->assertEquals('this_string_is_not_translated', $translator->translate('this_string_is_not_translated'));
    }
}
