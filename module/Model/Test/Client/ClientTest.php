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

use Database\Table\GroupMemberships;
use Laminas\Db\ResultSet\AbstractResultSet;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Model\Client\Client;
use Model\Client\CustomFieldManager;
use Model\Client\CustomFields;
use Model\Group\GroupManager;
use Model\Test\AbstractTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Protocol\Message\InventoryRequest;
use Protocol\Message\InventoryRequest\Content;
use Psr\Container\ContainerInterface;

class ClientTest extends AbstractTestCase
{
    use MockeryPHPUnitIntegration;

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

    public static function setGroupMembershipsNoActionProvider()
    {
        return array(
            array(
                array(),
                array(),
            ),
            array(
                array(),
                array('group1' => \Model\Client\Client::MEMBERSHIP_AUTOMATIC),
            ),
            array(
                array(2 => \Model\Client\Client::MEMBERSHIP_AUTOMATIC),
                array('group1' => \Model\Client\Client::MEMBERSHIP_AUTOMATIC),
            ),
            array(
                array(1 => \Model\Client\Client::MEMBERSHIP_AUTOMATIC),
                array('group1' => \Model\Client\Client::MEMBERSHIP_AUTOMATIC),
            ),
            array(
                array(1 => \Model\Client\Client::MEMBERSHIP_ALWAYS),
                array('group1' => \Model\Client\Client::MEMBERSHIP_ALWAYS),
            ),
            array(
                array(1 => \Model\Client\Client::MEMBERSHIP_NEVER),
                array('group1' => \Model\Client\Client::MEMBERSHIP_NEVER),
            ),
            array(
                array(),
                array('ignore' => \Model\Client\Client::MEMBERSHIP_ALWAYS),
            ),
        );
    }

    /**
     * @dataProvider setGroupMembershipsNoActionProvider
     */
    public function testSetGroupMembershipsNoAction($oldMemberships, $newMemberships)
    {
        $groupMemberships = $this->createMock(GroupMemberships::class);
        $groupMemberships->expects($this->never())->method('insert');
        $groupMemberships->expects($this->never())->method('update');
        $groupMemberships->expects($this->never())->method('delete');

        $groupManager = $this->createMock('Model\Group\GroupManager');
        $groupManager->method('getGroups')->with()->willReturn(
            array(
                array('Id' => 1, 'Name' => 'group1'),
                array('Id' => 2, 'Name' => 'group2'),
            )
        );

        $serviceManager = $this->createStub(ContainerInterface::class);
        $serviceManager->method('get')
            ->willReturnMap(
                array(
                    array('Database\Table\GroupMemberships', $groupMemberships),
                    array('Model\Group\GroupManager', $groupManager),
                )
            );

        $model = $this->createPartialMock(Client::class, ['offsetGet', 'getGroupMemberships']);
        $model->method('offsetGet')->with('Id')->willReturn(42);
        $model->method('getGroupMemberships')->willReturn($oldMemberships);
        $model->setContainer($serviceManager);

        $model->setGroupMemberships($newMemberships);
    }

    public static function setGroupMembershipsInsertProvider()
    {
        return array(
            array(
                array(),
                \Model\Client\Client::MEMBERSHIP_ALWAYS,
            ),
            array(
                array(),
                \Model\Client\Client::MEMBERSHIP_NEVER,
            ),
            array(
                array(2 => \Model\Client\Client::MEMBERSHIP_AUTOMATIC),
                \Model\Client\Client::MEMBERSHIP_ALWAYS,
            ),
            array(
                array(2 => \Model\Client\Client::MEMBERSHIP_AUTOMATIC),
                \Model\Client\Client::MEMBERSHIP_NEVER,
            ),
        );
    }

    /**
     * @dataProvider setGroupMembershipsInsertProvider
     */
    public function testSetGroupMembershipsInsert($oldMemberships, $newMembership)
    {
        $groupMemberships = $this->createMock(GroupMemberships::class);
        $groupMemberships->expects($this->once())->method('insert')->with(
            array(
                'hardware_id' => 42,
                'group_id' => 1,
                'static' => $newMembership,
            )
        );
        $groupMemberships->expects($this->never())->method('update');
        $groupMemberships->expects($this->never())->method('delete');

        $groupManager = $this->createMock('Model\Group\GroupManager');
        $groupManager->method('getGroups')->with()->willReturn(
            array(
                array('Id' => 1, 'Name' => 'group1'),
                array('Id' => 2, 'Name' => 'group2'),
            )
        );

        $serviceManager = $this->createStub(ContainerInterface::class);
        $serviceManager->method('get')
            ->willReturnMap(
                array(
                    array('Database\Table\GroupMemberships', $groupMemberships),
                    array('Model\Group\GroupManager', $groupManager),
                )
            );

        $model = $this->createPartialMock(Client::class, ['offsetGet', 'getGroupMemberships']);
        $model->method('offsetGet')->with('Id')->willReturn(42);
        $model->method('getGroupMemberships')->willReturn($oldMemberships);
        $model->setContainer($serviceManager);

        $model->setGroupMemberships(array('group1' => $newMembership));
    }

    public static function setGroupMembershipsUpdateProvider()
    {
        return array(
            array(
                \Model\Client\Client::MEMBERSHIP_AUTOMATIC,
                \Model\Client\Client::MEMBERSHIP_ALWAYS,
            ),
            array(
                \Model\Client\Client::MEMBERSHIP_AUTOMATIC,
                \Model\Client\Client::MEMBERSHIP_NEVER,
            ),
            array(
                \Model\Client\Client::MEMBERSHIP_ALWAYS,
                \Model\Client\Client::MEMBERSHIP_NEVER,
            ),
            array(
                \Model\Client\Client::MEMBERSHIP_NEVER,
                \Model\Client\Client::MEMBERSHIP_ALWAYS,
            ),
        );
    }

    /**
     * @dataProvider setGroupMembershipsUpdateProvider
     */
    public function testSetGroupMembershipsUpdate($oldMembership, $newMembership)
    {
        $groupMemberships = $this->createMock(GroupMemberships::class);
        $groupMemberships->expects($this->never())->method('insert');
        $groupMemberships->expects($this->once())->method('update')->with(
            array('static' => $newMembership),
            array(
                'hardware_id' => 42,
                'group_id' => 1,
            )
        );
        $groupMemberships->expects($this->never())->method('delete');

        $groupManager = $this->createMock('Model\Group\GroupManager');
        $groupManager->method('getGroups')->with()->willReturn(
            array(
                array('Id' => 1, 'Name' => 'group1'),
                array('Id' => 2, 'Name' => 'group2'),
            )
        );

        $serviceManager = $this->createStub(ContainerInterface::class);
        $serviceManager->method('get')
            ->willReturnMap(
                array(
                    array('Database\Table\GroupMemberships', $groupMemberships),
                    array('Model\Group\GroupManager', $groupManager),
                )
            );

        $model = $this->createPartialMock(Client::class, ['offsetGet', 'getGroupMemberships']);
        $model->method('offsetGet')->with('Id')->willReturn(42);
        $model->method('getGroupMemberships')->willReturn(array(1 => $oldMembership));
        $model->setContainer($serviceManager);

        $model->setGroupMemberships(array('group1' => $newMembership));
    }

    public static function setGroupMembershipsDeleteProvider()
    {
        return array(
            array(\Model\Client\Client::MEMBERSHIP_ALWAYS),
            array(\Model\Client\Client::MEMBERSHIP_NEVER),
        );
    }

    /**
     * @dataProvider setGroupMembershipsDeleteProvider
     */
    public function testSetGroupMembershipsDelete($oldMembership)
    {
        $group1 = $this->createMock('Model\Group\Group');
        $group1->method('offsetGet')->willReturnMap(
            array(array('Id', 1), array('Name', 'group1'))
        );
        $group1->expects($this->once())->method('update')->with(true);

        $group2 = $this->createMock('Model\Group\Group');
        $group2->method('offsetGet')->willReturnMap(
            array(array('Id', 2), array('Name', 'group2'))
        );
        $group2->expects($this->never())->method('update');

        $groupMemberships = $this->createMock(GroupMemberships::class);
        $groupMemberships->expects($this->never())->method('insert');
        $groupMemberships->expects($this->never())->method('update');
        $groupMemberships->expects($this->once())->method('delete')->with(
            array(
                'hardware_id' => 42,
                'group_id' => 1,
            )
        );

        $groupManager = $this->createMock('Model\Group\GroupManager');
        $groupManager->method('getGroups')->with()->willReturn(array($group1, $group2));

        $serviceManager = $this->createStub(ContainerInterface::class);
        $serviceManager->method('get')
            ->willReturnMap(
                array(
                    array('Database\Table\GroupMemberships', $groupMemberships),
                    array('Model\Group\GroupManager', $groupManager),
                )
            );

        $model = $this->createPartialMock(Client::class, ['offsetGet', 'getGroupMemberships']);
        $model->method('offsetGet')->with('Id')->willReturn(42);
        $model->method('getGroupMemberships')->willReturn([1 => $oldMembership]);
        $model->setContainer($serviceManager);

        $model->setGroupMemberships(array('group1' => \Model\Client\Client::MEMBERSHIP_AUTOMATIC));
    }

    public function testSetGroupMembershipsMixedKeys()
    {
        $groupMemberships = Mockery::mock(GroupMemberships::class);
        $groupMemberships->shouldReceive('insert')->once()->with([
            'hardware_id' => 42,
            'group_id' => 1,
            'static' => Client::MEMBERSHIP_ALWAYS,
        ]);
        $groupMemberships->shouldReceive('insert')->once()->with([
            'hardware_id' => 42,
            'group_id' => 3,
            'static' => Client::MEMBERSHIP_NEVER,
        ]);
        $groupMemberships->shouldNotReceive('update');
        $groupMemberships->shouldNotReceive('delete');

        $groupManager = $this->createMock('Model\Group\GroupManager');
        $groupManager->method('getGroups')->with()->willReturn(
            array(
                array('Id' => 1, 'Name' => 'group1'),
                array('Id' => 2, 'Name' => 'group2'),
                array('Id' => 3, 'Name' => 'group3'),
            )
        );

        $serviceManager = $this->createStub(ContainerInterface::class);
        $serviceManager->method('get')
            ->willReturnMap(
                array(
                    array('Database\Table\GroupMemberships', $groupMemberships),
                    array('Model\Group\GroupManager', $groupManager),
                )
            );

        $model = $this->createPartialMock(Client::class, ['offsetGet', 'getGroupMemberships']);
        $model->method('offsetGet')->with('Id')->willReturn(42);
        $model->method('getGroupMemberships')->willReturn(array(2 => \Model\Client\Client::MEMBERSHIP_ALWAYS));
        $model->setContainer($serviceManager);

        $model->setGroupMemberships(
            array(
                1 => \Model\Client\Client::MEMBERSHIP_ALWAYS,
                'group2' => \Model\Client\Client::MEMBERSHIP_ALWAYS,
                'group3' => \Model\Client\Client::MEMBERSHIP_NEVER,
            )
        );
    }

    public function testSetGroupMembershipsInvalidMembership()
    {
        $groupManager = $this->createMock('Model\Group\GroupManager');
        $groupManager->method('getGroups')->with()->willReturn(
            array(array('Id' => 1, 'Name' => 'group1'))
        );

        $serviceManager = $this->createStub(ContainerInterface::class);
        $serviceManager->method('get')
            ->willReturnMap(
                array(array('Model\Group\GroupManager', $groupManager))
            );

        $model = $this->createPartialMock(Client::class, ['offsetGet', 'getGroupMemberships']);
        $model->method('offsetGet')->with('Id')->willReturn(42);
        $model->method('getGroupMemberships')->willReturn(array());
        $model->setContainer($serviceManager);

        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Invalid membership type: 23');
        $model->setGroupMemberships(array('group1' => 23));
    }

    public static function getGroupMembershipsProvider()
    {
        return array(
            array(
                \Model\Client\Client::MEMBERSHIP_ANY,
                array(
                    1 => \Model\Client\Client::MEMBERSHIP_ALWAYS,
                    2 => \Model\Client\Client::MEMBERSHIP_NEVER,
                    3 => \Model\Client\Client::MEMBERSHIP_AUTOMATIC,
                )
            ),
            array(
                \Model\Client\Client::MEMBERSHIP_MANUAL,
                array(
                    1 => \Model\Client\Client::MEMBERSHIP_ALWAYS,
                    2 => \Model\Client\Client::MEMBERSHIP_NEVER,
                )
            ),
            array(
                \Model\Client\Client::MEMBERSHIP_ALWAYS,
                array(1 => \Model\Client\Client::MEMBERSHIP_ALWAYS)
            ),
            array(
                \Model\Client\Client::MEMBERSHIP_NEVER,
                array(2 => \Model\Client\Client::MEMBERSHIP_NEVER)
            ),
            array(
                \Model\Client\Client::MEMBERSHIP_AUTOMATIC,
                array(3 => \Model\Client\Client::MEMBERSHIP_AUTOMATIC)
            ),
        );
    }

    /**
     * @dataProvider getGroupMembershipsProvider
     */
    public function testGetGroupMemberships($type, $expected)
    {
        $groupManager = $this->createMock(GroupManager::class);
        $groupManager->expects($this->once())->method('updateCache');

        static::$serviceManager->setService(GroupManager::class, $groupManager);

        $model = new Client();
        $model->id = 1;
        $model->setContainer(static::$serviceManager);

        $this->assertSame($expected, $model->getGroupMemberships($type));
    }

    public function testGetGroupMembershipsInvalidType()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Bad value for membership: 42');

        $model = new Client();
        $model->setContainer(static::$serviceManager);
        $model->getGroupMemberships(42);
    }

    public function testGetGroups()
    {
        $groups = array('group1', 'group2');

        $groupManager = $this->createMock(GroupManager::class);
        $groupManager->method('getGroups')->with('Member', 42)->willReturn(
            new \ArrayIterator($groups)
        );
        static::$serviceManager->setService(GroupManager::class, $groupManager);

        $model = new Client();
        $model->setContainer(static::$serviceManager);
        $model->id = 42;

        $this->assertEquals($groups, $model->getGroups());
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
