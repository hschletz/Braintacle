<?php
/**
 * The Model module
 *
 * Copyright (C) 2011-2015 Holger Schletz <holger.schletz@web.de>
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
    /**
     * @internal
     */
    public function init(\Zend\ModuleManager\ModuleManagerInterface $manager)
    {
        $manager->loadModule('Database');
        $manager->loadModule('Model');
    }

    /**
     * @internal
     */
    public function getConfig()
    {
        return array(
            'service_manager' => array(
                'invokables' => array(
                    'Model\Client\CustomFields' => 'Model\Client\CustomFields',
                    'Model\Client\Item\AudioDevice' => 'Model\Client\Item\AudioDevice',
                    'Model\Client\Item\Controller' => 'Model\Client\Item\Controller',
                    'Model\Client\Item\Display' => 'Model\Client\Item\Display',
                    'Model\Client\Item\DisplayController' => 'Model\Client\Item\DisplayController',
                    'Model\Client\Item\ExtensionSlot' => 'Model\Client\Item\ExtensionSlot',
                    'Model\Client\Item\InputDevice' => 'Model\Client\Item\InputDevice',
                    'Model\Client\Item\MemorySlot' => 'Model\Client\Item\MemorySlot',
                    'Model\Client\Item\Modem' => 'Model\Client\Item\Modem',
                    'Model\Client\Item\MsOfficeProduct' => 'Model\Client\Item\MsOfficeProduct',
                    'Model\Client\Item\NetworkInterface' => 'Model\Client\Item\NetworkInterface',
                    'Model\Client\Item\Port' => 'Model\Client\Item\Port',
                    'Model\Client\Item\Printer' => 'Model\Client\Item\Printer',
                    'Model\Client\Item\VirtualMachine' => 'Model\Client\Item\VirtualMachine',
                    'Model\Client\WindowsInstallation' => 'Model\Client\WindowsInstallation',
                    'Model\Computer\Computer' => 'Model_Computer',
                    'Model\Computer\Software' => 'Model_Software',
                    'Model\Group\Group' => 'Model_Group',
                    'Model\Group\Membership' => 'Model_GroupMembership',
                    'Model\Network\Device' => 'Model\Network\Device',
                    'Model\Network\Subnet' => 'Model\Network\Subnet',
                    'Model\Operator\Operator' => 'Model\Operator\Operator',
                    'Model\Package\Assignment' => 'Model_PackageAssignment',
                    'Model\Package\Metadata' => 'Model\Package\Metadata',
                    'Model\Package\Package' => 'Model\Package\Package',
                    'Model\Registry\Value' => 'Model\Registry\Value',
                ),
                'factories' => array(
                    'Model\Client\ItemManager' => 'Model\Service\Client\ItemManagerFactory',
                    'Model\Client\CustomFieldManager' => 'Model\Service\Client\CustomFieldManagerFactory',
                    'Model\Client\DuplicatesManager' => 'Model\Service\Client\DuplicatesManagerFactory',
                    'Model\Config' => 'Model\Service\ConfigFactory',
                    'Model\Group\GroupManager' => 'Model\Service\Group\GroupManagerFactory',
                    'Model\Network\DeviceManager' => 'Model\Service\Network\DeviceManagerFactory',
                    'Model\Network\SubnetManager' => 'Model\Service\Network\SubnetManagerFactory',
                    'Model\Operator\OperatorManager' => 'Model\Service\Operator\OperatorManagerFactory',
                    'Model\Package\PackageManager' => 'Model\Service\Package\PackageManagerFactory',
                    'Model\Package\Storage\Direct' => 'Model\Service\Package\Storage\DirectFactory',
                    'Model\Registry\RegistryManager' => 'Model\Service\Registry\RegistryManagerFactory',
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
