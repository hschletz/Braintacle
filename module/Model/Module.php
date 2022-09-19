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
                ),
                'factories' => array(
                    'Model\Client\AndroidInstallation' => 'Laminas\ServiceManager\Factory\InvokableFactory',
                    'Model\Client\Client' => 'Model\Service\Client\ClientFactory',
                    'Model\Client\ClientManager' => 'Model\Service\Client\ClientManagerFactory',
                    'Model\Client\CustomFieldManager' => 'Model\Service\Client\CustomFieldManagerFactory',
                    'Model\Client\CustomFields' => 'Laminas\ServiceManager\Factory\InvokableFactory',
                    'Model\Client\DuplicatesManager' => 'Model\Service\Client\DuplicatesManagerFactory',
                    'Model\Client\ItemManager' => 'Model\Service\Client\ItemManagerFactory',
                    'Model\Client\Item\AudioDevice' => 'Laminas\ServiceManager\Factory\InvokableFactory',
                    'Model\Client\Item\Controller' => 'Laminas\ServiceManager\Factory\InvokableFactory',
                    'Model\Client\Item\Cpu' => 'Laminas\ServiceManager\Factory\InvokableFactory',
                    'Model\Client\Item\Display' => 'Laminas\ServiceManager\Factory\InvokableFactory',
                    'Model\Client\Item\DisplayController' => 'Laminas\ServiceManager\Factory\InvokableFactory',
                    'Model\Client\Item\ExtensionSlot' => 'Laminas\ServiceManager\Factory\InvokableFactory',
                    'Model\Client\Item\Filesystem' => 'Laminas\ServiceManager\Factory\InvokableFactory',
                    'Model\Client\Item\InputDevice' => 'Laminas\ServiceManager\Factory\InvokableFactory',
                    'Model\Client\Item\MemorySlot' => 'Laminas\ServiceManager\Factory\InvokableFactory',
                    'Model\Client\Item\Modem' => 'Laminas\ServiceManager\Factory\InvokableFactory',
                    'Model\Client\Item\MsOfficeProduct' => 'Laminas\ServiceManager\Factory\InvokableFactory',
                    'Model\Client\Item\NetworkInterface' => 'Laminas\ServiceManager\Factory\InvokableFactory',
                    'Model\Client\Item\Port' => 'Laminas\ServiceManager\Factory\InvokableFactory',
                    'Model\Client\Item\Printer' => 'Laminas\ServiceManager\Factory\InvokableFactory',
                    'Model\Client\Item\RegistryData' => 'Laminas\ServiceManager\Factory\InvokableFactory',
                    'Model\Client\Item\Sim' => 'Laminas\ServiceManager\Factory\InvokableFactory',
                    'Model\Client\Item\Software' => 'Laminas\ServiceManager\Factory\InvokableFactory',
                    'Model\Client\Item\StorageDevice' => 'Laminas\ServiceManager\Factory\InvokableFactory',
                    'Model\Client\Item\VirtualMachine' => 'Laminas\ServiceManager\Factory\InvokableFactory',
                    'Model\Client\WindowsInstallation' => 'Laminas\ServiceManager\Factory\InvokableFactory',
                    'Model\Config' => 'Model\Service\ConfigFactory',
                    'Model\Group\Group' => 'Model\Service\Group\GroupFactory',
                    'Model\Group\GroupManager' => 'Model\Service\Group\GroupManagerFactory',
                    'Model\Network\Device' => 'Laminas\ServiceManager\Factory\InvokableFactory',
                    'Model\Network\DeviceManager' => 'Model\Service\Network\DeviceManagerFactory',
                    'Model\Network\Subnet' => 'Laminas\ServiceManager\Factory\InvokableFactory',
                    'Model\Network\SubnetManager' => 'Model\Service\Network\SubnetManagerFactory',
                    'Model\Operator\AuthenticationService' => 'Model\Service\Operator\AuthenticationServiceFactory',
                    'Model\Operator\Operator' => 'Laminas\ServiceManager\Factory\InvokableFactory',
                    'Model\Operator\OperatorManager' => 'Model\Service\Operator\OperatorManagerFactory',
                    'Model\Package\Assignment' => 'Laminas\ServiceManager\Factory\InvokableFactory',
                    'Model\Package\Metadata' => 'Laminas\ServiceManager\Factory\InvokableFactory',
                    'Model\Package\Package' => 'Laminas\ServiceManager\Factory\InvokableFactory',
                    PackageBuilder::class => PackageBuilderFactory::class,
                    'Model\Package\PackageManager' => 'Model\Service\Package\PackageManagerFactory',
                    'Model\Package\Storage\Direct' => 'Model\Service\Package\Storage\DirectFactory',
                    'Model\Registry\RegistryManager' => 'Model\Service\Registry\RegistryManagerFactory',
                    'Model\Registry\Value' => 'Laminas\ServiceManager\Factory\InvokableFactory',
                    'Model\SoftwareManager' => 'Model\Service\SoftwareManagerFactory',
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
