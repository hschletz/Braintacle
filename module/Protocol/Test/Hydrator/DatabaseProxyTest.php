<?php

/**
 * Tests for DatabaseProxy hydrator
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

namespace Protocol\Test\Hydrator;

class DatabaseProxyTest extends \PHPUnit\Framework\TestCase
{
    public function testGetHydrator()
    {
        $hydrator = new \Laminas\Hydrator\ArraySerializableHydrator();
        $proxy = new \Protocol\Hydrator\DatabaseProxy($hydrator);
        $this->assertSame($hydrator, $proxy->getHydrator());
    }

    public function testHydrate()
    {
        $data = array('KEY1' => 'value1', 'KEY2' => 'value2');
        $object = new \ArrayObject();
        $hydrator = $this->createMock(\Laminas\Hydrator\HydratorInterface::class);
        $hydrator->expects($this->once())
                 ->method('hydrate')
                 ->with(array('key1' => 'value1', 'key2' => 'value2'))
                 ->willReturn($object);
        $proxy = new \Protocol\Hydrator\DatabaseProxy($hydrator);
        $this->assertSame($object, $proxy->hydrate($data, $object));
    }

    public function testExtract()
    {
        $object = new \ArrayObject();
        $hydrator = $this->createMock(\Laminas\Hydrator\HydratorInterface::class);
        $hydrator->expects($this->once())
                 ->method('extract')
                 ->with($object)
                 ->willReturn(array('key1' => 'value1', 'key2' => 'value2'));
        $proxy = new \Protocol\Hydrator\DatabaseProxy($hydrator);
        $this->assertSame(array('KEY1' => 'value1', 'KEY2' => 'value2'), $proxy->extract($object));
    }
}
