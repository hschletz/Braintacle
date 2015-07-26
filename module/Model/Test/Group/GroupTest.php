<?php
/**
 * Tests for Model\Group\Group
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

namespace Model\Test\Group;

class GroupTest extends \Model\Test\AbstractTest
{
    /** {@inheritdoc} */
    protected static $_tables = array(
        'ClientConfig',
        'ClientsAndGroups',
        'ClientSystemInfo',
        'Clients',
        'GroupInfo',
        'GroupMemberships',
        'Packages',
    );

    public function getDefaultConfigProvider()
    {
        return array(
            array('allowScan', 0, 'scannersPerSubnet', '0'),
            array('allowScan', 1, 'scannersPerSubnet', '2'),
            array('foo', 'bar', 'foo', 'bar'),
        );
    }
    /**
     * @dataProvider getDefaultConfigProvider
     */
    public function testGetDefaultConfig($option, $expectedValue, $globalOptionName, $globalOptionValue)
    {
        $config = $this->getMockBuilder('Model\Config')->disableOriginalConstructor()->getMock();
        $config->expects($this->once())->method('__get')->with($globalOptionName)->willReturn($globalOptionValue);
        $model = $this->_getModel(array('Model\Config' => $config));
        $this->assertEquals($expectedValue, $model->getDefaultConfig($option));
    }

    public function setMembersFromQueryProvider()
    {
        return array(
            array(\Model_GroupMembership::TYPE_STATIC, false, 'SetMembersFromQueryStatic'),
            array(\Model_GroupMembership::TYPE_EXCLUDED, true, 'SetMembersFromQueryExcluded'),
        );
    }

    /**
     * @dataProvider setMembersFromQueryProvider
     */
    public function testSetMembersFromQuery($type, $simulateLockFailure, $dataSet)
    {
        $clientManager = $this->getMock('Model_Computer');
        $clientManager->expects($this->once())
                      ->method('fetch')
                      ->with(
                          array('Id'),
                          null,
                          null,
                          'filter',
                          'search',
                          'operator',
                          'invert',
                          false,
                          true,
                          true
                      )->willReturn(array(array('Id' => 1), array('Id' => 2), array('Id' => 3), array('Id' => 5)));

        $serviceManager = $this->getMock('Zend\ServiceManager\ServiceManager');
        $serviceManager->method('get')
                       ->will(
                           $this->returnValueMap(
                               array(
                                   array('Model\Computer\Computer', true, $clientManager),
                                   array(
                                       'Database\Table\GroupMemberships',
                                       true,
                                       \Library\Application::getService('Database\Table\GroupMemberships')
                                   )
                               )
                           )
                       );

        $model = $this->getMockBuilder($this->_getClass())->setMethods(array('lock', 'unlock'))->getMock();
        if ($simulateLockFailure) {
            $model->expects($this->exactly(2))
                  ->method('lock')
                  ->will($this->onConsecutiveCalls(false, true));
        } else {
            $model->expects($this->once())->method('lock')->willReturn(true);
        }
        $model->expects($this->once())->method('unlock');
        $model['Id'] = 10;
        $model->setServiceLocator($serviceManager);

        $model->setMembersFromQuery($type, 'filter', 'search', 'operator', 'invert');
        $this->assertTablesEqual(
            $this->_loadDataSet($dataSet)->getTable('groups_cache'),
            $this->getConnection()->createQueryTable(
                'groups_cache', 'SELECT hardware_id, group_id, static FROM groups_cache ORDER BY group_id, hardware_id'
            )
        );
    }

    public function testSetMembersFromQueryExceptionInTransaction()
    {
        $clientManager = $this->getMock('Model_Computer');
        $clientManager->method('fetch')->willReturn(array(array('Id' => 1)));

        $connection = $this->getMock('Zend\Db\Adapter\Driver\AbstractConnection');
        $connection->expects($this->once())->method('beginTransaction');
        $connection->expects($this->once())->method('rollback');
        $connection->expects($this->never())->method('commit');

        $driver = $this->getMockBuilder('Zend\Db\Adapter\Driver\Pdo\Pdo')->disableOriginalConstructor()->getMock();
        $driver->method('getConnection')->willReturn($connection);
        $adapter = $this->getMockBuilder('Zend\Db\Adapter\Adapter')->disableOriginalConstructor()->getMock();
        $adapter->method('getDriver')->willReturn($driver);

        $select = $this->getMock('Zend\Db\Sql\Select');
        $select->method('columns')->will($this->returnSelf());
        $sql = $this->getMockBuilder('Zend\Db\Sql\Sql')->disableOriginalConstructor()->getMock();
        $sql->method('select')->willReturn($select);

        $groupMemberships = $this->getMockBuilder('Database\Table\GroupMemberships')
                                 ->disableOriginalConstructor()
                                 ->getMock();
        $groupMemberships->method('getAdapter')->willReturn($adapter);
        $groupMemberships->method('getSql')->willReturn($sql);
        $groupMemberships->method('selectWith')->willReturn(array());
        $groupMemberships->method('insert')->will($this->throwException(new \RuntimeException('test')));

        $serviceManager = $this->getMock('Zend\ServiceManager\ServiceManager');
        $serviceManager->method('get')
                       ->will(
                           $this->returnValueMap(
                               array(
                                   array('Model\Computer\Computer', true, $clientManager),
                                   array('Database\Table\GroupMemberships', true, $groupMemberships)
                               )
                           )
                       );

        $model = $this->getMockBuilder($this->_getClass())->setMethods(array('lock', 'unlock'))->getMock();
        $model->expects($this->once())->method('lock')->willReturn(true);
        $model->expects($this->once())->method('unlock');
        $model['Id'] = 10;
        $model->setServiceLocator($serviceManager);

        $this->setExpectedException('RuntimeException', 'test');
        $model->setMembersFromQuery(\Model_GroupMembership::TYPE_STATIC, 'filter', 'search', 'operator', 'invert');
    }

    public function testSetMembersFromQueryDynamic()
    {
        $select = $this->getMockBuilder('Zend_Db_Select')->disableOriginalConstructor()->getMock();
        $select->expects($this->once())->method('getPart')->with(\Zend_Db_Select::COLUMNS)->willReturn(array('id'));
        $select->method('__toString')->willReturn('query_new');

        $clientManager = $this->getMock('Model_Computer');
        $clientManager->expects($this->once())
                      ->method('fetch')
                      ->with(
                          array('Id'),
                          null,
                          null,
                          'filter',
                          'search',
                          'operator',
                          'invert',
                          false,
                          true,
                          false
                      )->willReturn($select);

        $serviceManager = $this->getMock('Zend\ServiceManager\ServiceManager');
        $serviceManager->method('get')
                       ->will(
                           $this->returnValueMap(
                               array(
                                   array('Model\Computer\Computer', true, $clientManager),
                                   array(
                                       'Database\Table\GroupInfo',
                                       true,
                                       \Library\Application::getService('Database\Table\GroupInfo')
                                   )
                               )
                           )
                       );

        $model = $this->getMockBuilder($this->_getClass())->setMethods(array('update'))->getMock();
        $model->expects($this->once())->method('update')->with(true);
        $model['Id'] = 10;
        $model->setServiceLocator($serviceManager);

        $model->setMembersFromQuery(\Model_GroupMembership::TYPE_DYNAMIC, 'filter', 'search', 'operator', 'invert');
        $this->assertEquals('query_new', $model['DynamicMembersSql']);
        $this->assertTablesEqual(
            $this->_loadDataSet('SetMembersFromQueryDynamic')->getTable('groups'),
            $this->getConnection()->createQueryTable(
                'groups', 'SELECT hardware_id, request FROM groups ORDER BY hardware_id'
            )
        );
    }

    public function testSetMembersFromQueryDynamicInvalidQuery()
    {
        $select = $this->getMockBuilder('Zend_Db_Select')->disableOriginalConstructor()->getMock();
        $select->expects($this->once())->method('getPart')->with(\Zend_Db_Select::COLUMNS)->willReturn(array(1, 2));
        $select->expects($this->never())->method('__toString');

        $clientManager = $this->getMock('Model_Computer');
        $clientManager->method('fetch')->willReturn($select);

        $model = $this->_getModel(array('Model\Computer\Computer' => $clientManager));
        $model['Id'] = 10;

        $this->setExpectedException('LogicException', 'Expected 1 column, got 2');
        $model->setMembersFromQuery(\Model_GroupMembership::TYPE_DYNAMIC, 'filter', 'search', 'operator', 'invert');
    }

    public function updateProvider()
    {
        return array(
            array(true, false, null, true, null), // force update, but no query
            array(true, true, null, false, null), // force update, but locking fails
            array(false, true, new \DateTime('2015-07-23 20:21:00'), true, null), // not expired yet
            array(true, true, new \DateTime('2015-07-23 20:21:00'), true, 'Update'), // not expired, but forced
            array(false, true, new \DateTime('2015-07-23 20:19:00'), true, 'Update'), // expired
            array(false, true, null, true, 'Update'), // no cache yet
        );
    }
    /**
     * @dataProvider updateProvider
     */
    public function testUpdate($force, $setSql, $expires, $lockSuccess, $dataSet)
    {
        $now = new \DateTime('2015-07-23 20:20:00');

        $random = $this->getMock('Library\Random');
        $random->method('getInteger')->willReturn(42);

        $config = $this->getMockBuilder('Model\Config')->disableOriginalConstructor()->getMock();
        $config->method('__get')->will(
            $this->returnValueMap(
                array(
                    array('groupCacheExpirationInterval', 600),
                    array('groupCacheExpirationFuzz', 60),
                )
            )
        );

        $serviceManager = $this->getMock('Zend\ServiceManager\ServiceManager');
        $serviceManager->method('get')
                       ->will(
                           $this->returnValueMap(
                               array(
                                   array(
                                       'Database\Table\Clients',
                                       true,
                                       \Library\Application::getService('Database\Table\Clients')
                                   ),
                                   array(
                                       'Database\Table\GroupInfo',
                                       true,
                                       \Library\Application::getService('Database\Table\GroupInfo')
                                   ),
                                   array(
                                       'Database\Table\GroupMemberships',
                                       true,
                                       \Library\Application::getService('Database\Table\GroupMemberships')
                                   ),
                                   array('Library\Now', true, $now),
                                   array('Library\Random', true, $random),
                                   array('Model\Config', true, $config),
                               )
                           )
                       );

        $model = $this->getMockBuilder($this->_getClass())->setMethods(array('lock', 'unlock'))->getMock();
        $model->method('lock')->willReturn($lockSuccess);
        if ($dataSet !== null) {
            $model->expects($this->once())->method('unlock');
        }
        $model->setServiceLocator($serviceManager);
        $model['Id'] = 10;
        $model['DynamicMembersSql'] = $setSql ? 'SELECT id FROM hardware WHERE id IN(2,3,4,5)' : null;
        $model['CacheCreationDate'] = null;
        $model['CacheExpirationDate'] = $expires;

        $model->update($force);
        // CacheCreationDate is only updated when there was data to alter ($dataSet !== null)
        $this->assertEquals(
            ($dataSet === null) ? null : $now,
            $model['CacheCreationDate']
        );
        // CacheExpirationDate is either updated ($dataSet !== null) or kept at initialized value
        $this->assertEquals(
            ($dataSet === null) ? $expires : new \DateTime('2015-07-23 20:30:42'),
            $model['CacheExpirationDate']
        );
        $this->assertTablesEqual(
            $this->_loadDataSet($dataSet)->getTable('groups'),
            $this->getConnection()->createQueryTable(
                'groups', 'SELECT hardware_id, request, create_time, revalidate_from FROM groups ORDER BY hardware_id'
            )
        );
    }

    public function testGetPackagesDefaultOrder()
    {
        $model = $this->_getModel();
        $model['Id'] = 10;
        $this->assertEquals(array('package1', 'package2'), $model->getPackages());
    }

    public function testGetPackagesReverseOrder()
    {
        $model = $this->_getModel();
        $model['Id'] = 10;
        $this->assertEquals(array('package2', 'package1'), $model->getPackages('desc'));
    }
}
