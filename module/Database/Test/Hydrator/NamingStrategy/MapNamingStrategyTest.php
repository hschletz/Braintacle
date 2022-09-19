<?php

/**
 * Tests for MapNamingStrategy
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

namespace Database\Test\Hydrator\NamingStrategy;

class MapNamingStrategyTest extends \PHPUnit\Framework\TestCase
{
    public function testGetMapswithAutomaticExtractionMap()
    {
        $hydrationMap = ['extracted' => 'hydrated'];
        $extractionMap = ['hydrated' => 'extracted'];

        $namingStrategy = new \Database\Hydrator\NamingStrategy\MapNamingStrategy($hydrationMap);
        $this->assertEquals($hydrationMap, $namingStrategy->getHydrationMap());
        $this->assertEquals($extractionMap, $namingStrategy->getExtractionMap());
    }

    public function testGetMapswithManualExtractionMap()
    {
        $hydrationMap = ['extracted1' => 'hydrated1'];
        $extractionMap = ['hydrated2' => 'extracted2'];

        $namingStrategy = new \Database\Hydrator\NamingStrategy\MapNamingStrategy($hydrationMap, $extractionMap);
        $this->assertEquals($hydrationMap, $namingStrategy->getHydrationMap());
        $this->assertEquals($extractionMap, $namingStrategy->getExtractionMap());
    }

    public function testHydrateValid()
    {
        $mapping = array(
            'extracted1' => 'hydrated1',
            'extracted2' => 'hydrated2',
            'noop1' => 'noop1',
            'noop2' => 'noop2',
        );
        $namingStrategy = new \Database\Hydrator\NamingStrategy\MapNamingStrategy($mapping);
        $this->assertEquals('hydrated1', $namingStrategy->hydrate('extracted1'));
        $this->assertEquals('hydrated1', $namingStrategy->hydrate('hydrated1'));
        $this->assertEquals('hydrated2', $namingStrategy->hydrate('extracted2'));
        $this->assertEquals('hydrated2', $namingStrategy->hydrate('hydrated2'));
        $this->assertEquals('noop1', $namingStrategy->hydrate('noop1'));
        $this->assertEquals('noop2', $namingStrategy->hydrate('noop2'));
    }

    public function testHydrateInvalid()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Unknown column name: invalid');
        $namingStrategy = new \Database\Hydrator\NamingStrategy\MapNamingStrategy(array('extracted1' => 'hydrated1'));
        $namingStrategy->hydrate('invalid');
    }

    public function testExtractValid()
    {
        $namingStrategy = new \Database\Hydrator\NamingStrategy\MapNamingStrategy(array('extracted' => 'hydrated'));
        $this->assertEquals('extracted', $namingStrategy->extract('hydrated'));
    }

    public function testExtractInvalid()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Unknown property name: invalid');
        $namingStrategy = new \Database\Hydrator\NamingStrategy\MapNamingStrategy(array('extracted1' => 'hydrated1'));
        $namingStrategy->extract('invalid');
    }
}
