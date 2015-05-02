<?php
/**
 * Abstract test class for hydrators
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

namespace Database\Test\Hydrator;

abstract class AbstractHydratorTest extends \PHPUnit_Framework_TestCase
{
    protected function _getHydrator()
    {
        $class = preg_replace('/Test\\\\?/', '', get_class($this));
        return new $class;
    }

    /**
     * @dataProvider hydrateProvider
     */
    public function testHydrate(array $data, array $objectData)
    {
        $hydrator = $this->_getHydrator();
        $object = new \ArrayObject;
        $this->assertSame($object, $hydrator->hydrate($data, $object));
        $this->assertEquals($objectData, $object->getArrayCopy());
    }

    /**
     * @dataProvider extractProvider
     */
    public function testExtract(array $objectData, array $data)
    {
        $hydrator = $this->_getHydrator();
        $object = new \ArrayObject($objectData);
        $this->assertEquals($data, $hydrator->extract($object));
    }
}
