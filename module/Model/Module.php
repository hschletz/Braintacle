<?php
/**
 * The Model module
 *
 * Copyright (C) 2011-2018 Holger Schletz <holger.schletz@web.de>
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
    Feature\ConfigProviderInterface,
    Feature\InitProviderInterface
{
    /** {@inheritdoc} */
    public function init(\Zend\ModuleManager\ModuleManagerInterface $manager)
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
                    'Zend\Authentication\AuthenticationService' => 'Model\Operator\AuthenticationService',
                ),
                'factories' => array(
                    'Model\Client\Client' => 'Model\Service\Client\ClientFactory',
                    'Model\Client\ClientManager' => 'Model\Service\Client\ClientManagerFactory',
                    'Model\Client\CustomFieldManager' => 'Model\Service\Client\CustomFieldManagerFactory',
                    'Model\Client\CustomFields' => 'Zend\ServiceManager\Factory\InvokableFactory',
                    'Model\Client\DuplicatesManager' => 'Model\Service\Client\DuplicatesManagerFactory',
                    'Model\Client\ItemManager' => 'Model\Service\Client\ItemManagerFactory',
                    'Model\Client\Item\AudioDevice' => 'Zend\ServiceManager\Factory\InvokableFactory',
                    'Model\Client\Item\Controller' => 'Zend\ServiceManager\Factory\InvokableFactory',
                    'Model\Client\Item\Cpu' => 'Zend\ServiceManager\Factory\InvokableFactory',
                    'Model\Client\Item\Display' => 'Zend\ServiceManager\Factory\InvokableFactory',
                    'Model\Client\Item\DisplayController' => 'Zend\ServiceManager\Factory\InvokableFactory',
                    'Model\Client\Item\ExtensionSlot' => 'Zend\ServiceManager\Factory\InvokableFactory',
                    'Model\Client\Item\Filesystem' => 'Zend\ServiceManager\Factory\InvokableFactory',
                    'Model\Client\Item\InputDevice' => 'Zend\ServiceManager\Factory\InvokableFactory',
                    'Model\Client\Item\MemorySlot' => 'Zend\ServiceManager\Factory\InvokableFactory',
                    'Model\Client\Item\Modem' => 'Zend\ServiceManager\Factory\InvokableFactory',
                    'Model\Client\Item\MsOfficeProduct' => 'Zend\ServiceManager\Factory\InvokableFactory',
                    'Model\Client\Item\NetworkInterface' => 'Zend\ServiceManager\Factory\InvokableFactory',
                    'Model\Client\Item\Port' => 'Zend\ServiceManager\Factory\InvokableFactory',
                    'Model\Client\Item\Printer' => 'Zend\ServiceManager\Factory\InvokableFactory',
                    'Model\Client\Item\RegistryData' => 'Zend\ServiceManager\Factory\InvokableFactory',
                    'Model\Client\Item\Sim' => 'Zend\ServiceManager\Factory\InvokableFactory',
                    'Model\Client\Item\Software' => 'Zend\ServiceManager\Factory\InvokableFactory',
                    'Model\Client\Item\StorageDevice' => 'Zend\ServiceManager\Factory\InvokableFactory',
                    'Model\Client\Item\VirtualMachine' => 'Zend\ServiceManager\Factory\InvokableFactory',
                    'Model\Client\WindowsInstallation' => 'Zend\ServiceManager\Factory\InvokableFactory',
                    'Model\Config' => 'Model\Service\ConfigFactory',
                    'Model\Group\Group' => 'Model\Service\Group\GroupFactory',
                    'Model\Group\GroupManager' => 'Model\Service\Group\GroupManagerFactory',
                    'Model\Network\Device' => 'Zend\ServiceManager\Factory\InvokableFactory',
                    'Model\Network\DeviceManager' => 'Model\Service\Network\DeviceManagerFactory',
                    'Model\Network\Subnet' => 'Zend\ServiceManager\Factory\InvokableFactory',
                    'Model\Network\SubnetManager' => 'Model\Service\Network\SubnetManagerFactory',
                    'Model\Operator\AuthenticationService' => 'Model\Service\Operator\AuthenticationServiceFactory',
                    'Model\Operator\Operator' => 'Zend\ServiceManager\Factory\InvokableFactory',
                    'Model\Operator\OperatorManager' => 'Model\Service\Operator\OperatorManagerFactory',
                    'Model\Package\Assignment' => 'Zend\ServiceManager\Factory\InvokableFactory',
                    'Model\Package\Metadata' => 'Zend\ServiceManager\Factory\InvokableFactory',
                    'Model\Package\Package' => 'Zend\ServiceManager\Factory\InvokableFactory',
                    'Model\Package\PackageManager' => 'Model\Service\Package\PackageManagerFactory',
                    'Model\Package\Storage\Direct' => 'Model\Service\Package\Storage\DirectFactory',
                    'Model\Registry\RegistryManager' => 'Model\Service\Registry\RegistryManagerFactory',
                    'Model\Registry\Value' => 'Zend\ServiceManager\Factory\InvokableFactory',
                    'Model\SoftwareManager' => 'Model\Service\SoftwareManagerFactory',
                ),
                'shared' => array(
                    'Model\Package\Metadata' => false,
                    'Model\Package\Storage\Direct' => false,
                ),
            ),
        );
    }

    /** {@inheritdoc} */
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
    public static function getPath($path = '')
    {
        return \Library\Application::getPath('module/Model/' . $path);
    }
}
