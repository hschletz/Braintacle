<?php

/**
 * Abstract test class for hydrators
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

namespace Library\Test\Hydrator;

use Model\AbstractModel;
use stdClass;

abstract class AbstractHydratorTest extends \PHPUnit\Framework\TestCase
{
    protected function getHydrator()
    {
        $class = preg_replace('/Test\\\\?/', '', get_class($this));
        return new $class();
    }

    /**
     * @dataProvider hydrateProvider
     */
    public function testHydrateWithStdClass(array $data, array $objectData)
    {
        $hydrator = $this->getHydrator();
        $object = new stdClass();
        $this->assertSame($object, $hydrator->hydrate($data, $object));
        $this->assertEquals($objectData, get_object_vars($object));
    }

    /**
     * @dataProvider hydrateProvider
     */
    public function testHydrateWithAbstractModel(array $data, array $objectData)
    {
        $hydrator = $this->getHydrator();
        $object = $this->getMockForAbstractClass(AbstractModel::class);
        $this->assertSame($object, $hydrator->hydrate($data, $object));
        $expected = [];
        foreach ($objectData as $key => $value) {
            $expected[ucfirst($key)] = $value;
        }
        $this->assertEquals($expected, $object->getArrayCopy());
    }

    /**
     * @dataProvider extractProvider
     */
    public function testExtractWithStdClass(array $objectData, array $data)
    {
        $hydrator = $this->getHydrator();
        $object = (object) $objectData;
        $this->assertEquals($data, $hydrator->extract($object));
    }

    /**
     * @dataProvider extractProvider
     */
    public function testExtractWithAbstractModel(array $objectData, array $data)
    {
        $hydrator = $this->getHydrator();
        $object = $this->getMockForAbstractClass(AbstractModel::class);
        foreach ($objectData as $key => $value) {
            $object->$key = $value;
        }
        $this->assertEquals($data, $hydrator->extract($object));
    }
}
