<?php
/**
 * The Model module
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

namespace Model;

use Zend\ModuleManager\Feature;

/**
 * The Model module
 *
 * This module provides models as services. Most services are not shared, i.e.
 * the service manager creates a new instance every time a model is requested.
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
            'service_manager' => array(
                'invokables' => array(
                    'Model\Computer\Windows' => 'Model_Windows',
                ),
                'shared' => array(
                    'Model\Computer\Windows' => false,
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
}
