<?php
/**
 * The Library module
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

namespace Library;

use Zend\ModuleManager\Feature;

/**
 * The Library module
 * 
 * This module provides a library of general purpose classes (not specific to
 * other modules). Provided view helpers etc. are automatically registered and
 * don't need to be loaded explicitly.
 *
 * @codeCoverageIgnore
 */
class Module implements
Feature\AutoloaderProviderInterface,
Feature\ConfigProviderInterface
{
    /**
     * @internal
     */
    public function getDependencies()
    {
        return array('Database');
    }

    /**
     * @internal
     */
    public function getConfig()
    {
        return array(
            'controller_plugins' => array(
                'invokables' => array(
                    'RedirectToRoute' => 'Library\Mvc\Controller\Plugin\RedirectToRoute',
                    'UrlFromRoute' => 'Library\Mvc\Controller\Plugin\UrlFromRoute',
                )
            ),
            'service_manager' => array(
                'aliases' => array(
                    'Zend\Authentication\AuthenticationService' => 'Library\AuthenticationService',
                ),
                'factories' => array(
                    '\Library\Logger' => 'Library\Log\LoggerServiceFactory',
                ),
                'invokables' => array(
                    'Library\AuthenticationService' => '\Library\Authentication\AuthenticationService',
                ),
            ),
            'view_helpers' => array(
                'factories' => array(
                    'formYesNo' => 'Library\View\Helper\Service\FormYesNoFactory',
                    'htmlTag' => 'Library\View\Helper\Service\HtmlTagFactory',
                ),
            ),
        );
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
        \Zend\Filter\StaticFilter::getPluginManager()->setInvokableClass(
            'Library\FixEncodingErrors',
            'Library\Filter\FixEncodingErrors'
        );
    }
}
