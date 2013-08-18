<?php
/**
 * The Console module
 *
 * Copyright (C) 2011-2013 Holger Schletz <holger.schletz@web.de>
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

namespace Console;

use Zend\ModuleManager\Feature;

/**
 * This is the module for the web administration console.
 */
class Module implements
Feature\InitProviderInterface,
Feature\ConfigProviderInterface,
Feature\AutoloaderProviderInterface,
Feature\BootstrapListenerInterface
{
    /**
     * @internal
     */
    public function init(\Zend\ModuleManager\ModuleManagerInterface $manager)
    {
        $manager->loadModule('Library');
    }

    /**
     * @internal
     */
    public function getConfig()
    {
        return require(__DIR__ . '/../module.config.php');
    }

    /**
     * @internal
     */
    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__,
                ),
            ),
        );
    }

    /**
     * @internal
     */
    public function onBootstrap(\Zend\EventManager\EventInterface $e)
    {
        $e->getParam('application')->getEventManager()->attach('render', array($this, 'setLayoutTitle'));
    }

    /**
     * Hook to set the page title
     *
     * This is invoked by the "render" event.
     *
     * @param \Zend\Mvc\MvcEvent $e MVC event
     */
    public function setLayoutTitle(\Zend\Mvc\MvcEvent $e)
    {
        $headTitleHelper = $e->getApplication()->getServiceManager()->get('viewHelperManager')->get('headTitle');
        $headTitleHelper->setTranslatorEnabled(false);
        $headTitleHelper->append('Braintacle'); // TODO: append page-specific information
    }
}
