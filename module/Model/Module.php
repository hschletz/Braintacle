<?php
/**
 * The Model module
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

namespace Model;

use Zend\ModuleManager\Feature;

/**
 * The Model module
 *
 * This module provides models as services. The services are shared, i.e. the
 * returned objects should not be modifed, but used as a prototype by cloning
 * where necessary.
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
        return array('Database', 'Library');
    }

    /**
     * @internal
     */
    public function getConfig()
    {
        return array(
            'service_manager' => array(
                'invokables' => array(
                    'Model\Computer\Computer' => 'Model_Computer',
                    'Model\Computer\Windows' => 'Model_Windows',
                ),
                'factories' => array(
                    'Model\Computer\Duplicates' => 'Model\Service\Computer\DuplicatesFactory',
                    'Model\Config' => 'Model\Service\ConfigFactory',
                    'Model\Operator' => 'Model\Service\OperatorFactory',
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
     * Get path to module directory
     *
     * @param string $path Optional path component that is appended to the module root path
     * @return string Absolute path to requested file/directory (directories without trailing slash)
     */
    public static function getPath($path='')
    {
        return \Library\Application::getPath('module/Model/' . $path);
    }
}
