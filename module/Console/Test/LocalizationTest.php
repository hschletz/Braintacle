<?php
/**
 * Tests for localization (except form data)
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

namespace Console\Test;

class LocalizationTest extends \PHPUnit_Framework_TestCase
{
    protected $serverBackup;
    protected $localeBackup;
    protected $defaultTranslatorBackup;

    public function setUp()
    {
        // Preserve global state
        $this->serverBackup = $_SERVER;
        $this->localeBackup = \Locale::getDefault();
        $this->defaultTranslatorBackup = \Zend\Validator\AbstractValidator::getDefaultTranslator();
    }

    public function tearDown()
    {
        // Restore global state
        $_SERVER = $this->serverBackup;
        \Locale::setDefault($this->localeBackup);
        \Zend\Validator\AbstractValidator::setDefaultTranslator($this->defaultTranslatorBackup);
    }

    public function testDefaultLocaleUnchanged()
    {
        unset($_SERVER['HTTP_ACCEPT_LANGUAGE']);
        \Locale::setDefault('fi');
        \Library\Application::init('Console', true);
        $this->assertEquals('fi', \Locale::getDefault());
    }

    public function testDefaultLocaleFromHttpHeader()
    {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'se-SE,se;q=0.8,en-US;q=0.6,en;q=0.4';
        \Locale::setDefault('fi');
        \Library\Application::init('Console', true);
        $this->assertEquals('se_SE', \Locale::getDefault());
    }

    public function testDefaultTranslator()
    {
        $translator = $this->createMock('Zend\Mvc\I18n\Translator');
        $application = \Library\Application::init('Console', true);

        // Run test initializion after application initialization because it
        // would be overwritten otherwise
        \Zend\Validator\AbstractValidator::setDefaultTranslator(null);
        $serviceManager = $application->getServiceManager();
        $serviceManager->setAllowOverride(true);
        $serviceManager->setService('MvcTranslator', $translator);

        // Invoke bootstrap event handler manually. It has already been run
        // during application initialization, but we changed the default
        // translator in the meantime.
        (new \Console\Module)->onBootstrap($application->getMvcEvent());
        $this->assertSame($translator, \Zend\Validator\AbstractValidator::getDefaultTranslator());
    }
}
