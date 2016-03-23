<?php
/**
 * Abstract controller test case
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

/**
 * Abstract controller test case
 *
 * This base class performs common setup for all coltroller tests.
 */
abstract class AbstractControllerTest extends \Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase
{
    /**
     * Set up application config
     */
    public function setUp()
    {
        parent::setUp();

        $this->setTraceError(true);
        $this->setApplicationConfig(\Library\Application::getService('ApplicationConfig'));
    }

    /**
     * Get instance of a controller plugin
     *
     * @param string $name Plugin name
     * @return \Zend\Mvc\Controller\Plugin\PluginInterface Plugin instance
     */
    protected function _getControllerPlugin($name)
    {
        return \Library\Application::getService('ControllerPluginManager')->get($name);
    }

    /**
     * Replace MvcTranslator service with a dummy translator to allow injecting test messages without warning
     */
    protected function _disableTranslator()
    {
        $this->getApplication()->getServiceManager()->setService(
            'MvcTranslator',
            new \Zend\Mvc\I18n\Translator(new \Zend\I18n\Translator\Translator)
        );
    }
}
