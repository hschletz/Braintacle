<?php

/**
 * Tests for AbstractTable helper methods
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

namespace Database\Test\Table;

use Database\AbstractTable;
use Laminas\Db\Sql\Select;
use Laminas\Hydrator\AbstractHydrator;
use Laminas\Hydrator\HydratorInterface;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Tests for AbstractTable helper methods
 */
class AbstractTableTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Table class
     * @var \Database\AbstractTable
     */
    protected $_table;

    /**
     * Select Mock
     * @var MockObject|Select
     */
    protected $_select;

    public function setUp(): void
    {
        // Set up mock objects for getCol()

        $this->_select = $this->createMock('Laminas\Db\Sql\Select');

        $sql = $this->createMock('Laminas\Db\Sql\Sql');
        $sql->method('select')->willReturn($this->_select);

        $this->_table = $this->createPartialMock(AbstractTable::class, ['getSql', 'selectWith']);
        $this->_table->method('getSql')->willReturn($sql);

        parent::setUp();
    }

    public function testGetHydrator()
    {
        $hydrator = $this->createStub(HydratorInterface::class);
        $property = new \ReflectionProperty('Database\AbstractTable', '_hydrator');
        $property->setAccessible(true);
        $property->setValue($this->_table, $hydrator);
        $this->assertSame($hydrator, $this->_table->getHydrator());
    }

    public function testGetConnection()
    {
        $connection = $this->createMock('Laminas\Db\Adapter\Driver\ConnectionInterface');

        $driver = $this->createMock('Laminas\Db\Adapter\Driver\DriverInterface');
        $driver->method('getConnection')->willReturn($connection);

        $adapter = $this->createMock('Laminas\Db\Adapter\AdapterInterface');
        $adapter->method('getDriver')->willReturn($driver);

        $table = $this->createPartialMock(AbstractTable::class, ['getAdapter']);
        $table->method('getAdapter')->willReturn($adapter);

        $this->assertSame($connection, $table->getConnection());
    }

    public function testFetchColWithData()
    {
        $this->_select->expects($this->once())->method('columns')->with(array('col'), false);
        $this->_table->method('selectWith')->with($this->_select)->willReturn(
            array(
                array('col' => 'value1'),
                array('col' => 'value2')
            )
        );

        $this->assertEquals(array('value1', 'value2'), $this->_table->fetchCol('col'));
    }

    public function testFetchColWithAbstractHydrator()
    {
        $hydrator = $this->createMock(AbstractHydrator::class);
        $hydrator->method('hydrateName')->with('col')->willReturn('hydrated');

        $resultSet = $this->createMock('Laminas\Db\ResultSet\HydratingResultSet');
        $resultSet->method('getHydrator')->willReturn($hydrator);
        $resultSet->method('valid')->willReturnOnConsecutiveCalls(true, true, false);
        $resultSet->method('key')->willReturnOnConsecutiveCalls(0, 1);
        $resultSet->method('current')->willReturnOnConsecutiveCalls(
            array('hydrated' => 'value1'),
            array('hydrated' => 'value2')
        );

        $this->_select->expects($this->once())->method('columns')->with(array('col'), false);
        $this->_table->method('selectWith')->with($this->_select)->willReturn($resultSet);

        $this->assertEquals(array('value1', 'value2'), $this->_table->fetchCol('col'));
    }

    public function testFetchColWithOtherHydrator()
    {
        $hydrator = $this->createStub(HydratorInterface::class);

        $resultSet = $this->createMock('Laminas\Db\ResultSet\HydratingResultSet');
        $resultSet->method('getHydrator')->willReturn($hydrator);
        $resultSet->method('valid')->willReturnOnConsecutiveCalls(true, true, false);
        $resultSet->method('key')->willReturnOnConsecutiveCalls(0, 1);
        $resultSet->method('current')->willReturnOnConsecutiveCalls(
            array('col' => 'value1'),
            array('col' => 'value2')
        );

        $this->_select->expects($this->once())->method('columns')->with(array('col'), false);
        $this->_table->method('selectWith')->with($this->_select)->willReturn($resultSet);

        $this->assertEquals(array('value1', 'value2'), $this->_table->fetchCol('col'));
    }

    public function testFetchColWithEmptyTable()
    {
        $this->_select->expects($this->once())->method('columns')->with(array('col'), false);
        $this->_table->method('selectWith')->with($this->_select)->willReturn(array());

        $this->assertSame(array(), $this->_table->fetchCol('col'));
    }
}
