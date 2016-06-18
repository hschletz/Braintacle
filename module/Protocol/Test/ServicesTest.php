<?php
/**
 * Tests for service manager configuration
 *
 * Copyright (C) 2011-2016 Holger Schletz <holger.schletz@web.de>
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

class ServicesTest extends \PHPUnit_Framework_TestCase
{
    protected static $_serviceManager;

    public static function setUpBeforeClass()
    {
        $application = \Zend\Mvc\Application::init(\Library\Application::getApplicationConfig('Protocol'));
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
        // Complete test setup on first run (cannot create mock object in static setup)
        if (!static::$_serviceManager->has('Db', false)) {
            static::$_serviceManager->setService(
                'Db',
                $this->getMockBuilder('Zend\Db\Adapter\Adapter')->disableOriginalConstructor()->getMock()
            );
        }
        $this->assertEquals($class, get_class(static::$_serviceManager->get($service)));
    }
}
