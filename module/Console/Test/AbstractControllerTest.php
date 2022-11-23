<?php

/**
 * Abstract controller test case
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

use Laminas\Mvc\MvcEvent;
use Library\Application;

/**
 * Abstract controller test case
 *
 * This base class performs common setup for all controller tests.
 */
abstract class AbstractControllerTest extends \Laminas\Test\PHPUnit\Controller\AbstractHttpControllerTestCase
{
    /**
     * Set up application config
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->setTraceError(true);
        $this->setApplicationConfig(\Library\Application::getApplicationConfig('Console', false));

        // Put application in authenticated state
        $auth = $this->createMock('Model\Operator\AuthenticationService');
        $auth->expects($this->atLeastOnce())->method('hasIdentity')->willReturn(true);

        $serviceManager = $this->getApplicationServiceLocator();
        $serviceManager->setService('Model\Operator\AuthenticationService', $auth);
        $serviceManager->setService(
            'Library\UserConfig',
            array(
                'debug' => array(
                    'display backtrace' => true,
                    'report missing translations' => true,
                ),
            )
        );
        Application::addAbstractFactories($serviceManager);
    }

    public function testRedirectToLoginPage()
    {
        $serviceLocator = $this->getApplicationServiceLocator();
        $serviceLocator->setAllowOverride(true);

        // Call method on overridden service to satisfy atLeastOnce constraint
        $serviceLocator->get('Model\Operator\AuthenticationService')->hasIdentity();

        // Reset application to unauthenticated state
        $auth = $this->createMock('Model\Operator\AuthenticationService');
        $auth->expects($this->atLeastOnce())->method('hasIdentity')->willReturn(false);
        $serviceLocator->setService('Model\Operator\AuthenticationService', $auth);

        $uri = '/console/' . strtolower(preg_replace('/(.*\\\\|ControllerTest)/', '', get_class($this)));
        $this->dispatch($uri);
        $this->assertRedirectTo('/console/login/login/');

        $session = new \Laminas\Session\Container('login');
        $this->assertEquals($uri, $session->originalUri);
    }

    /**
     * Get instance of a controller plugin
     *
     * @param string $name Plugin name
     * @return \Laminas\Mvc\Controller\Plugin\PluginInterface Plugin instance
     */
    protected function getControllerPlugin($name)
    {
        return $this->getApplicationServiceLocator()->get('ControllerPluginManager')->get($name);
    }

    /**
     * Replace MvcTranslator service with a dummy translator to allow injecting test messages without warning
     */
    protected function disableTranslator()
    {
        $serviceManager = $this->getApplicationServiceLocator();
        $serviceManager->setAllowOverride(true);
        $serviceManager->setService(
            'MvcTranslator',
            new \Laminas\Mvc\I18n\Translator(new \Laminas\I18n\Translator\Translator())
        );
    }

    /**
     * Bypass all MvcEvent::EVENT_RENDER listeners.
     *
     * This is useful to test the action result directly via assertMvcResult().
     */
    protected function interceptRenderEvent(): void
    {
        $this->application->getEventManager()->attach(
            MvcEvent::EVENT_RENDER,
            function ($event) {
                $event->stopPropagation(true);
            },
            100
        );
    }

    /**
     * Test result of MVC action.
     */
    public function assertMvcResult($result)
    {
        $this->assertSame(
            $result,
            $this->getApplication()->getMvcEvent()->getResult(),
            'Failed asserting the MVC result.'
        );
    }
}
