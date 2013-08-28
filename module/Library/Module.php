<?php
/**
 * The Library module
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

namespace Library;

use Zend\ModuleManager\Feature;

/**
 * The Library module
 * 
 * This module provides a library of general purpose classes (not specific to
 * other modules). Provided view helpers etc. are automatically registered and
 * don't need to be loaded explicitly.
 *
 * It also processes configuration from /config/braintacle.ini and sets up the
 * "Db" service which provides a configured Zend\Db\Adapter\Adapter instance.
 *
 * @codeCoverageIgnore
 */
class Module implements Feature\ConfigProviderInterface, Feature\AutoloaderProviderInterface
{
    /**
     * @internal
     */
    public function getConfig()
    {
        // Static configuration part
        $config = array(
            'controller_plugins' => array(
                'invokables' => array(
                    'RedirectToRoute' => 'Library\Mvc\Controller\Plugin\RedirectToRoute',
                    'UrlFromRoute' => 'Library\Mvc\Controller\Plugin\UrlFromRoute',
                )
            ),
            'service_manager' => array(
                'factories' => array(
                    'Db' => 'Zend\Db\Adapter\AdapterServiceFactory',
                ),
            ),
            'view_helpers' => array(
                'invokables' => array(
                    'htmlTag' => 'Library\View\Helper\HtmlTag',
                ),
            ),
        );

        // Merge user configuration from /config/braintacle.ini
        $ini = \Zend\Config\Factory::fromFile(__DIR__ . '/../../config/braintacle.ini');
        $config['db'] = $ini['database'];
        $config['db']['charset'] = 'utf8';

        return $config;
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
}