<?php
/**
 * Tests for the Translator setup
 *
 * Copyright (C) 2011-2014 Holger Schletz <holger.schletz@web.de>
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
    public function testMissingTranslationTriggersNotineInDevelopmentMode()
    {
        // Repeat application initialization with production environment
        putenv('APPLICATION_ENV=production');
        \Library\Application::init('Library', false);

        // Invoke translator with untranslatable string - must not trigger notice
        $translator = \Library\Application::getService('MvcTranslator')->getTranslator();
        $message = $translator->translate('this_string_is_not_translated');

        // Reset application state ASAP.
        putenv('APPLICATION_ENV=test');
        \Library\Application::init('Library', false);

        $this->assertEquals('this_string_is_not_translated', $message);

        // Repeat test - must trigger notice this time
        @trigger_error(''); // Bring error_get_last() into defined state
        $translator = \Library\Application::getService('MvcTranslator')->getTranslator();
        $message = @$translator->translate('this_string_is_not_translated');
        $lastError = error_get_last();
        $this->assertEquals(E_USER_NOTICE, $lastError['type']);
        $this->assertEquals(
            'Missing translation: this_string_is_not_translated',
            $lastError['message']
        );
        $this->assertEquals('this_string_is_not_translated', $message);
    }

    public function testNoTranslatorForEnglishLocale()
    {
        // Preserve state
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $language = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
        }
        // Repeat application initialization with english locale
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'en_UK';
        \Library\Application::init('Library', false);

        // Invoke translator with untranslatable string - must not trigger notice
        $translator = \Library\Application::getService('MvcTranslator')->getTranslator();
        $message = $translator->translate('this_string_is_not_translated');

        // Reset application state ASAP.
        if (isset($language)) {
            $_SERVER['HTTP_ACCEPT_LANGUAGE'] = $language;
        } else {
            unset($_SERVER['HTTP_ACCEPT_LANGUAGE']);
        }
        \Library\Application::init('Library', false);

        $this->assertEquals('this_string_is_not_translated', $message);

        // No translations should be loaded
        $reflectionObject = new \ReflectionObject($translator);
        $reflectionProperty = $reflectionObject->getProperty('files');
        $reflectionProperty->setAccessible(true);
        $this->assertSame(array(), $reflectionProperty->getValue($translator));
    }
}
