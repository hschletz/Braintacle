<?php

/**
 * The Model module
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

namespace Model;

use Laminas\ModuleManager\Feature;
use Model\Package\PackageBuilder;
use Model\Package\Storage\Direct;
use Model\Package\Storage\StorageInterface;
use Model\Service\Package\PackageBuilderFactory;

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
    Feature\ConfigProviderInterface,
    Feature\InitProviderInterface
{
    /** {@inheritdoc} */
    public function init(\Laminas\ModuleManager\ModuleManagerInterface $manager)
    {
        $manager->loadModule('Database');
        $manager->loadModule('Library');
        $manager->loadModule('Protocol');
    }

    /** {@inheritdoc} */
    public function getConfig()
    {
        return array(
            'service_manager' => array(
                'aliases' => array(
                    'Laminas\Authentication\AuthenticationService' => 'Model\Operator\AuthenticationService',
                    StorageInterface::class => Direct::class, // this is the only implementation so far
                ),
                'factories' => array(
                    'Model\Client\Client' => 'Model\Service\Client\ClientFactory',
                    'Model\Group\Group' => 'Model\Service\Group\GroupFactory',
                    'Model\Operator\AuthenticationService' => 'Model\Service\Operator\AuthenticationServiceFactory',
                ),
                'shared' => array(
                    'Model\Package\Metadata' => false,
                    'Model\Package\Storage\Direct' => false,
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
    public static function getPath($path = '')
    {
        return \Library\Application::getPath('module/Model/' . $path);
    }
}
