<?php
/**
 * Tests for AbstractTable helper methods
 *
 * Copyright (C) 2011-2017 Holger Schletz <holger.schletz@web.de>
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

/**
 * Tests for AbstractTable helper methods
 */
class AbstractTableTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Table class
     * @var \Database\AbstractTable
     */
    protected $_table;

    /**
     * Select Mock
     * @var \Zend\Db\Sql\Select
     */
    protected $_select;

    public function setUp()
    {
        // Set up mock objects for getCol()

        $this->_select = $this->createMock('Zend\Db\Sql\Select');

        $sql = $this->createMock('Zend\Db\Sql\Sql');
        $sql->method('select')->willReturn($this->_select);

        $this->_table = $this->getMockBuilder('Database\AbstractTable')
                             ->disableOriginalConstructor()
                             ->setMethods(array('getSql', 'selectWith'))
                             ->getMockForAbstractClass();
        $this->_table->method('getSql')->willReturn($sql);

        return parent::setUp();
    }

    public function testGetHydrator()
    {
        $hydrator = new \ReflectionProperty('Database\AbstractTable', '_hydrator');
        $hydrator->setAccessible(true);
        $hydrator->setValue($this->_table, 'the hydrator');
        $this->assertEquals('the hydrator', $this->_table->getHydrator());
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
        $hydrator = $this->getMockBuilder('Zend\Hydrator\AbstractHydrator')
                         ->setMethods(array('hydrateName'))
                         ->getMockForAbstractClass();
        $hydrator->method('hydrateName')->with('col')->willReturn('hydrated');

        $resultSet = $this->createMock('Zend\Db\ResultSet\HydratingResultSet');
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
        $hydrator = new \Database\Test\TestHydrator;

        $resultSet = $this->createMock('Zend\Db\ResultSet\HydratingResultSet');
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
