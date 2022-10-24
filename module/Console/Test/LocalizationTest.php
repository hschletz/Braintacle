<?php

/**
 * Tests for localization (except form data)
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

namespace Console\Test;

use Laminas\Mvc\I18n\Translator;

class LocalizationTest extends \PHPUnit\Framework\TestCase
{
    protected $serverBackup;
    protected $localeBackup;
    protected $defaultTranslatorBackup;

    public function setUp(): void
    {
        // Preserve global state
        $this->serverBackup = $_SERVER;
        $this->localeBackup = \Locale::getDefault();
        $this->defaultTranslatorBackup = \Laminas\Validator\AbstractValidator::getDefaultTranslator();
    }

    public function tearDown(): void
    {
        // Restore global state
        $_SERVER = $this->serverBackup;
        \Locale::setDefault($this->localeBackup);
        \Laminas\Validator\AbstractValidator::setDefaultTranslator($this->defaultTranslatorBackup);
    }

    public function testDefaultLocaleUnchanged()
    {
        unset($_SERVER['HTTP_ACCEPT_LANGUAGE']);
        \Locale::setDefault('fi');
        \Library\Application::init('Console');
        $this->assertEquals('fi', \Locale::getDefault());
    }

    public function testDefaultLocaleFromHttpHeader()
    {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'sv-se,sv;q=0.8,en-us;q=0.6,en;q=0.4';
        \Locale::setDefault('fi');
        \Library\Application::init('Console');
        $this->assertEquals('sv_SE', \Locale::getDefault());
    }

    public function testDefaultTranslator()
    {
        $translator = $this->createMock('Laminas\Mvc\I18n\Translator');
        $application = \Library\Application::init('Console');

        // Run test initializion after application initialization because it
        // would be overwritten otherwise
        \Laminas\Validator\AbstractValidator::setDefaultTranslator(null);
        $serviceManager = $application->getServiceManager();
        $serviceManager->setAllowOverride(true);
        $serviceManager->setService(Translator::class, $translator);
        $serviceManager->setService('Library\UserConfig', array());

        // Trigger route event which has a handler taking care of the
        // initialization. Detach other listeners first which are irrelevant for
        // this test and which would require additional dependencies.
        $event = $application->getMvcEvent();
        $event->setName(\Laminas\Mvc\MvcEvent::EVENT_ROUTE);
        $eventManager = $application->getEventManager();
        $eventManager->detach(array($serviceManager->get('ModuleManager')->getModule('Console'), 'forceLogin'));
        $eventManager->triggerEvent($event);

        $this->assertSame($translator, \Laminas\Validator\AbstractValidator::getDefaultTranslator());
    }
}
