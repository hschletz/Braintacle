<?php

/**
 * Abstract strategy test case
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

namespace Database\Test\Hydrator\Strategy;

abstract class AbstractStrategyTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Strategy instance
     * @var \Laminas\Hydrator\Strategy\StrategyInterface
     */
    protected $_strategy;

    public function setUp(): void
    {
        $class = get_class($this);
        $class = substr($class, strlen('Database\Test\Hydrator\Strategy'), -4);
        $class = '\Database\Hydrator\Strategy' . $class;
        $this->_strategy = new $class();
    }

    public function testInterface()
    {
        $this->assertInstanceOf(
            'Laminas\Hydrator\Strategy\StrategyInterface',
            $this->_strategy
        );
    }

    /**
     * @dataProvider hydrateProvider
     */
    public function testHydrate($value, $expected)
    {
        $this->assertSame($expected, $this->_strategy->hydrate($value, null));
    }

    /**
     * @dataProvider extractProvider
     */
    public function testExtract($value, $expected)
    {
        $this->assertSame($expected, $this->_strategy->extract($value));
    }
}
