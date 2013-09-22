<?php
/**
 * The Database module
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

namespace Database;

use Zend\ModuleManager\Feature;

/**
 * The Database module
 *
 * This module provides a low level interface to the database. It is used by the
 * model classes and for managing the database structure.
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
            'service_manager' => array(
                'factories' => array(
                    'Db' => 'Zend\Db\Adapter\AdapterServiceFactory',
                ),
            ),
        );

        // Merge database configuration from /config/braintacle.ini
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
