<?php
/**
 * Tests for Model\Client\Client
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

namespace Model\Test\Client;

class ClientTest extends \Model\Test\AbstractTest
{
    protected static $_tables = array(
        'ClientsAndGroups',
        'WindowsProductKeys',
        'WindowsInstallations',
        'DuplicateSerials',
        'DuplicateAssetTags',
    );

    public function testObjectProperties()
    {
        $model = $this->_getModel();
        $this->assertInstanceOf('ArrayAccess', $model);
        $this->assertTrue(method_exists($model, 'exchangeArray'));
    }

    public function testOffsetGetExistingProperty()
    {
        $model = new \Model\Client\Client(array('key' => 'value'));
        $this->assertEquals('value', $model['key']);
    }

    public function testOffsetGetWindowsNotNull()
    {
        $serviceManager = $this->getMock('Zend\ServiceManager\ServiceManager');
        $serviceManager->expects($this->once())->method('get')->willReturnCallback(
            function($name) {
                // Proxy to real service manager. Mock only exists to assert
                // that the service is used only once.
                return \Library\Application::getService($name);
            }
        );

        $model = new \Model\Client\Client(array('Id' => 2));
        $model->setServiceLocator($serviceManager);

        $windows = $model['Windows'];
        $this->assertInstanceOf('Model\Client\WindowsInstallation', $windows);
        $this->assertEquals(
            array(
                'Workgroup' => 'workgroup2',
                'UserDomain' => 'userdomain2',
                'Company' => 'company2',
                'Owner' => 'owner2',
                'ProductKey' => 'product_key2',
                'ProductId' => 'product_id2',
                'ManualProductKey' => 'manual_product_key2',
            ),
            $windows->getArrayCopy()
        );
        $this->assertSame($windows, $model['Windows']); // cached result
    }

    public function testOffsetGetWindowsNull()
    {
        $serviceManager = $this->getMock('Zend\ServiceManager\ServiceManager');
        $serviceManager->expects($this->once())->method('get')->willReturnCallback(
            function($name) {
                // Proxy to real service manager. Mock only exists to assert
                // that the service is used only once.
                return \Library\Application::getService($name);
            }
        );

        $model = new \Model\Client\Client(array('Id' => 3));
        $model->setServiceLocator($serviceManager);

        $this->assertNull($model['Windows']);
        $this->assertNull($model['Windows']); // cached result
    }

    public function testOffsetGetCustomFields()
    {
        $customFieldManager = $this->getMockBuilder('Model\Client\CustomFieldManager')
                                   ->disableOriginalConstructor()
                                   ->getMock();
        $customFieldManager->expects($this->once())->method('read')->with(2)->willReturn('custom_fields');

        $model = $this->_getModel(array('Model\Client\CustomFieldManager' => $customFieldManager));
        $model['Id'] = 2;
        $this->assertEquals('custom_fields', $model['CustomFields']);
        $this->assertEquals('custom_fields', $model['CustomFields']); // cached result
    }

    public function testOffsetGetRegistry()
    {
        $model = new \Model\Client\Client(array('Registry.Content' => 'something'));
        $this->assertEquals('something', $model['Registry.Something']);
    }

    public function offsetGetBlacklistedProvider()
    {
        return array(
            array('IsSerialBlacklisted', 'Serial', 'serial_good', false),
            array('IsSerialBlacklisted', 'Serial', 'serial_bad', true),
            array('IsAssetTagBlacklisted', 'AssetTag', 'assettag_good', false),
            array('IsAssetTagBlacklisted', 'AssetTag', 'assettag_bad', true),
        );
    }

    /**
     * @dataProvider offsetGetBlacklistedProvider
     */
    public function testOffsetGetBlacklisted($index, $initialIndex, $initialValue, $result)
    {
        $serviceManager = $this->getMock('Zend\ServiceManager\ServiceManager');
        $serviceManager->expects($this->once())->method('get')->willReturnCallback(
            function($name) {
                // Proxy to real service manager. Mock only exists to assert
                // that the service is used only once.
                return \Library\Application::getService($name);
            }
        );
        $model = new \Model\Client\Client(array($initialIndex => $initialValue));
        $model->setServiceLocator($serviceManager);
        $this->assertSame($result, $model[$index]);
        $this->assertSame($result, $model[$index]); // cached result
    }

    public function testOffsetGetItems()
    {
        $model = $this->getMockBuilder('Model\Client\Client')->setMethods(array('getItems'))->getMock();
        $model->expects($this->once())->method('getItems')->with('type')->willReturn('items');
        $this->assertEquals('items', $model['type']);
        $this->assertEquals('items', $model['type']); // cached result
    }

    public function testGetItemsDefaultArgs()
    {
        $itemManager = $this->getMockBuilder('Model\Client\ItemManager')->disableOriginalConstructor()->getMock();
        $itemManager->expects($this->once())
                    ->method('getItems')
                    ->with('type', array('Client' => 42), null, null)
                    ->willReturn('result');

        $serviceManager = $this->getMock('Zend\ServiceManager\ServiceManager');
        $serviceManager->method('get')->with('Model\Client\ItemManager')->willReturn($itemManager);

        $model = $this->getMockBuilder('Model\Client\Client')->setMethods(array('offsetGet'))->getMock();
        $model->method('offsetGet')->with('Id')->willReturn(42);
        $model->setServiceLocator($serviceManager);

        $this->assertEquals('result', $model->getItems('type'));
    }

    public function testGetItemsCustomArgs()
    {
        $itemManager = $this->getMockBuilder('Model\Client\ItemManager')->disableOriginalConstructor()->getMock();
        $itemManager->expects($this->once())
                    ->method('getItems')
                    ->with('type', array('filter' => 'arg', 'Client' => 42), 'order', 'direction')
                    ->willReturn('result');

        $serviceManager = $this->getMock('Zend\ServiceManager\ServiceManager');
        $serviceManager->method('get')->with('Model\Client\ItemManager')->willReturn($itemManager);

        $model = $this->getMockBuilder('Model\Client\Client')->setMethods(array('offsetGet'))->getMock();
        $model->method('offsetGet')->with('Id')->willReturn(42);
        $model->setServiceLocator($serviceManager);

        $this->assertEquals('result', $model->getItems('type', 'order', 'direction', array('filter' => 'arg')));
    }
}
