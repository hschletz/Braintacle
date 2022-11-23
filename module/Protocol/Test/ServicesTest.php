<?php

/**
 * Tests for service manager configuration
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

namespace Protocol\Test;

use Library\Application;

class ServicesTest extends \PHPUnit\Framework\TestCase
{
    protected static $_serviceManager;

    public static function setUpBeforeClass(): void
    {
        $application = \Library\Application::init('Protocol');
        static::$_serviceManager = $application->getServiceManager();
    }

    public function servicesProvider()
    {
        return array(
            array('Protocol\Hydrator\AudioDevices', 'Protocol\Hydrator\DatabaseProxy'),
            array('Protocol\Hydrator\ClientsBios', 'Protocol\Hydrator\ClientsBios'),
            array('Protocol\Hydrator\ClientsHardware', 'Protocol\Hydrator\ClientsHardware'),
            array('Protocol\Hydrator\Controllers', 'Protocol\Hydrator\DatabaseProxy'),
            array('Protocol\Hydrator\Cpu', 'Protocol\Hydrator\DatabaseProxy'),
            array('Protocol\Hydrator\DisplayControllers', 'Protocol\Hydrator\DatabaseProxy'),
            array('Protocol\Hydrator\Displays', 'Protocol\Hydrator\DatabaseProxy'),
            array('Protocol\Hydrator\ExtensionSlots', 'Protocol\Hydrator\DatabaseProxy'),
            array('Protocol\Hydrator\Filesystems', 'Protocol\Hydrator\Filesystems'),
            array('Protocol\Hydrator\InputDevices', 'Protocol\Hydrator\DatabaseProxy'),
            array('Protocol\Hydrator\MemorySlots', 'Protocol\Hydrator\DatabaseProxy'),
            array('Protocol\Hydrator\Modems', 'Protocol\Hydrator\DatabaseProxy'),
            array('Protocol\Hydrator\MsOfficeProducts', 'Protocol\Hydrator\DatabaseProxy'),
            array('Protocol\Hydrator\NetworkInterfaces', 'Protocol\Hydrator\DatabaseProxy'),
            array('Protocol\Hydrator\Ports', 'Protocol\Hydrator\DatabaseProxy'),
            array('Protocol\Hydrator\Printers', 'Protocol\Hydrator\DatabaseProxy'),
            array('Protocol\Hydrator\RegistryData', 'Protocol\Hydrator\DatabaseProxy'),
            array('Protocol\Hydrator\Sim', 'Protocol\Hydrator\DatabaseProxy'),
            array('Protocol\Hydrator\Software', 'Protocol\Hydrator\Software'),
            array('Protocol\Hydrator\StorageDevices', 'Protocol\Hydrator\DatabaseProxy'),
            array('Protocol\Hydrator\VirtualMachines', 'Protocol\Hydrator\DatabaseProxy'),
            array('Protocol\Message\InventoryRequest', 'Protocol\Message\InventoryRequest'),
        );
    }

    /**
     * @dataProvider servicesProvider
     */
    public function testServices($service, $class)
    {
        // Create temporary service manager with identical configuration.
        $config = static::$_serviceManager->get('config');
        $serviceManager = new \Laminas\ServiceManager\ServiceManager($config['service_manager']);
        $serviceManager->setService('config', $config);
        $serviceManager->setService('Db', $this->createMock('Laminas\Db\Adapter\Adapter'));
        Application::addAbstractFactories($serviceManager);

        $this->assertEquals($class, get_class($serviceManager->get($service)));
    }
}
