<?php
/**
 * Tests for MapNamingStrategy
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

namespace Database\Test\Hydrator\NamingStrategy;

class MapNamingStrategyTest extends \PHPUnit_Framework_TestCase
{
    public function testHydrateValid()
    {
        $namingStrategy = new \Database\Hydrator\NamingStrategy\MapNamingStrategy(array('extracted' => 'hydrated'));
        $this->assertEquals('hydrated', $namingStrategy->hydrate('extracted'));
    }

    public function testHydrateInvalid()
    {
        $this->setExpectedException('InvalidArgumentException', 'Unknown column name: extracted');
        $namingStrategy = new \Database\Hydrator\NamingStrategy\MapNamingStrategy(array());
        $namingStrategy->hydrate('extracted');
    }

    public function testExtractValid()
    {
        $namingStrategy = new \Database\Hydrator\NamingStrategy\MapNamingStrategy(array('extracted' => 'hydrated'));
        $this->assertEquals('extracted', $namingStrategy->extract('hydrated'));
    }

    public function testExtractInvalid()
    {
        $this->setExpectedException('InvalidArgumentException', 'Unknown property name: hydrated');
        $namingStrategy = new \Database\Hydrator\NamingStrategy\MapNamingStrategy(array());
        $namingStrategy->extract('hydrated');
    }

    public function testGetHydratorMap()
    {
        $namingStrategy = new \Database\Hydrator\NamingStrategy\MapNamingStrategy(array('extracted' => 'hydrated'));
        $this->assertEquals(array('extracted' => 'hydrated'), $namingStrategy->getHydratorMap());
    }

    public function testGetExtractorMap()
    {
        $namingStrategy = new \Database\Hydrator\NamingStrategy\MapNamingStrategy(array('extracted' => 'hydrated'));
        $this->assertEquals(array('hydrated' => 'extracted'), $namingStrategy->getExtractorMap());
    }
}
