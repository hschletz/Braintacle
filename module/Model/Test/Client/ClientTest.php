<?php

/**
 * Tests for Model\Client\Client
 *
 * Copyright (C) 2011-2025 Holger Schletz <holger.schletz@web.de>
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

use Laminas\Db\ResultSet\AbstractResultSet;
use Model\Client\Client;
use Model\Client\CustomFieldManager;
use Model\Client\CustomFields;
use Model\Test\AbstractTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Protocol\Message\InventoryRequest;
use Protocol\Message\InventoryRequest\Content;
use Psr\Container\ContainerInterface;

class ClientTest extends AbstractTestCase
{
    protected static $_tables = [
        'WindowsInstallations',
        'DuplicateSerials',
        'DuplicateAssetTags',
    ];

    public function testObjectProperties()
    {
        $model = $this->getModel();
        $this->assertInstanceOf('ArrayAccess', $model);
        $this->assertTrue(method_exists($model, 'exchangeArray'));
    }

    public function testOffsetGetExistingProperty()
    {
        $model = new Client(['Key' => 'value']);
        $this->assertEquals('value', $model['Key']);
    }

    public function testOffsetGetAndroidNotNull()
    {
        $serviceManager = $this->createMock(ContainerInterface::class);
        $serviceManager->expects($this->once())->method('get')->willReturnCallback(
            function ($name) {
                // Proxy to real service manager. Mock only exists to assert
                // that the service is used only once.
                return static::$serviceManager->get($name);
            }
        );

        $model = new Client();
        $model->id = 3;
        $model->setContainer($serviceManager);

        $android = $model['Android'];
        $this->assertInstanceOf('Model\Client\AndroidInstallation', $android);
        $this->assertEquals(
            [
                'Country' => 'country',
                'JavaVm' => 'java_vm',
                'JavaInstallationDirectory' => 'java_installation_directory',
                'JavaClassPath' => 'java_class_path',
            ],
            $android->getArrayCopy()
        );
        $this->assertSame($android, $model['Android']); // cached result
    }

    public function testOffsetGetAndroidNull()
    {
        $serviceManager = $this->createMock(ContainerInterface::class);
        $serviceManager->expects($this->once())->method('get')->willReturnCallback(
            function ($name) {
                // Proxy to real service manager. Mock only exists to assert
                // that the service is used only once.
                return static::$serviceManager->get($name);
            }
        );

        $model = new Client();
        $model->id = 2;
        $model->setContainer($serviceManager);

        $this->assertNull($model['Android']);
        $this->assertNull($model['Android']); // cached result
    }

    public function testOffsetGetWindowsNotNull()
    {
        $serviceManager = $this->createMock(ContainerInterface::class);
        $serviceManager->expects($this->once())->method('get')->willReturnCallback(
            function ($name) {
                // Proxy to real service manager. Mock only exists to assert
                // that the service is used only once.
                return static::$serviceManager->get($name);
            }
        );

        $model = new Client();
        $model->id = 2;
        $model->setContainer($serviceManager);

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
                'CpuArchitecture' => 'cpu_architecture2',
            ),
            $windows->getArrayCopy()
        );
        $this->assertSame($windows, $model['Windows']); // cached result
    }

    public function testOffsetGetWindowsNull()
    {
        $serviceManager = $this->createMock(ContainerInterface::class);
        $serviceManager->expects($this->once())->method('get')->willReturnCallback(
            function ($name) {
                // Proxy to real service manager. Mock only exists to assert
                // that the service is used only once.
                return static::$serviceManager->get($name);
            }
        );

        $model = new Client();
        $model->id = 3;
        $model->setContainer($serviceManager);

        $this->assertNull($model['Windows']);
        $this->assertNull($model['Windows']); // cached result
    }

    public function testOffsetGetCustomFields()
    {
        $customFields = $this->createStub(CustomFields::class);

        $customFieldManager = $this->createMock('Model\Client\CustomFieldManager');
        $customFieldManager->expects($this->once())->method('read')->with(2)->willReturn($customFields);
        static::$serviceManager->setService(CustomFieldManager::class, $customFieldManager);

        $model = new Client();
        $model->setContainer(static::$serviceManager);
        $model->id = 2;

        $this->assertEquals($customFields, $model->customFields);
        $this->assertEquals($customFields, $model->customFields); // cached result
    }

    public function testOffsetGetRegistry()
    {
        $model = new \Model\Client\Client(array('Registry.Content' => 'something'));
        $this->assertEquals('something', $model['Registry.Something']);
    }

    public static function offsetGetBlacklistedProvider()
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
        $serviceManager = $this->createMock(ContainerInterface::class);
        $serviceManager->expects($this->once())->method('get')->willReturnCallback(
            function ($name) {
                // Proxy to real service manager. Mock only exists to assert
                // that the service is used only once.
                return static::$serviceManager->get($name);
            }
        );
        $model = new Client();
        $model->$initialIndex = $initialValue;
        $model->setContainer($serviceManager);
        $this->assertSame($result, $model[$index]);
        $this->assertSame($result, $model[$index]); // cached result
    }

    public function testOffsetGetItems()
    {
        /** @var MockObject|Client */
        $model = $this->createPartialMock(Client::class, ['getItems']);
        $model->expects($this->once())->method('getItems')->with('ItemType')->willReturn('items');
        $this->assertEquals('items', $model['ItemType']);
        $this->assertEquals('items', $model['ItemType']); // cached result
    }

    public function testGetDownloadedPackageIds()
    {
        $model = new Client();
        $model->setContainer(static::$serviceManager);
        $model->id = 1;
        $this->assertEquals(array(1, 2), $model->getDownloadedPackageIds());
    }

    public function testGetItemsDefaultArgs()
    {
        $result = $this->createStub(AbstractResultSet::class);

        $itemManager = $this->createMock('Model\Client\ItemManager');
        $itemManager->expects($this->once())
            ->method('getItems')
            ->with('type', array('Client' => 42), null, null)
            ->willReturn($result);

        $serviceManager = $this->createMock(ContainerInterface::class);
        $serviceManager->method('get')->with('Model\Client\ItemManager')->willReturn($itemManager);

        $model = $this->createPartialMock(Client::class, ['offsetGet']);
        $model->method('offsetGet')->with('Id')->willReturn(42);
        $model->setContainer($serviceManager);

        $this->assertSame($result, $model->getItems('type'));
    }

    public function testGetItemsCustomArgs()
    {
        $result = $this->createStub(AbstractResultSet::class);

        $itemManager = $this->createMock('Model\Client\ItemManager');
        $itemManager->expects($this->once())
            ->method('getItems')
            ->with('type', array('filter' => 'arg', 'Client' => 42), 'order', 'direction')
            ->willReturn($result);

        $serviceManager = $this->createMock(ContainerInterface::class);
        $serviceManager->method('get')->with('Model\Client\ItemManager')->willReturn($itemManager);

        $model = $this->createPartialMock(Client::class, ['offsetGet']);
        $model->method('offsetGet')->with('Id')->willReturn(42);
        $model->setContainer($serviceManager);

        $this->assertSame($result, $model->getItems('type', 'order', 'direction', array('filter' => 'arg')));
    }

    public function testSetCustomFields()
    {
        $data = ['key' => 'value'];

        $customFieldManager = $this->createMock(CustomFieldManager::class);
        $customFieldManager->expects($this->once())->method('write')->with(42, $data);
        static::$serviceManager->setService(CustomFieldManager::class, $customFieldManager);

        $model = new Client();
        $model->setContainer(static::$serviceManager);
        $model->id = 42;

        $model->setCustomFields($data);
    }

    public function testToDomDocument()
    {
        $serviceManager = $this->createMock(ContainerInterface::class);

        $client = new Client();
        $client->setContainer($serviceManager);

        // DOMDocument constructor must be preserved. Otherwise setting the
        // formatOutput property would have no effect for whatever reason.
        $inventoryRequest = $this->getMockBuilder(InventoryRequest::class)
            ->setConstructorArgs([$this->createStub(Content::class)])
            ->getMock();
        // loadClient() is invoked once per instance, but the invocation counter
        // is not affected by cloning. This test invokes toDomDocument() twice.
        $inventoryRequest->expects($this->exactly(2))->method('loadClient')->with($client);

        $serviceManager->method('get')->with(InventoryRequest::class)->willReturn($inventoryRequest);

        $document1 = $client->toDomDocument();
        $this->assertInstanceOf(InventoryRequest::class, $document1);
        $this->assertTrue($document1->formatOutput);

        $document2 = $client->toDomDocument();
        $this->assertInstanceOf(InventoryRequest::class, $document2);
        $this->assertTrue($document2->formatOutput);

        $this->assertNotSame($document1, $document2);
    }
}
