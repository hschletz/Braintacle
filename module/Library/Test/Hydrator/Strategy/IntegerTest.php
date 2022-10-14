<?php

/**
 * Tests for the Integer strategy
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

namespace Library\Test\Hydrator\Strategy;

use stdClass;

class IntegerTest extends \PHPUnit\Framework\TestCase
{
    public function hydrateProvider()
    {
        return array(
            array(0, 0),
            array(1, 1),
            array('0', 0),
            array('1', 1),
            array(null, null),
        );
    }

    /**
     * @dataProvider hydrateProvider
     */
    public function testHydrate($value, $expected)
    {
        $strategy = new \Library\Hydrator\Strategy\Integer();
        $this->assertSame($expected, $strategy->hydrate($value, null));
    }

    public function hydrateInvalidDatatypeProvider()
    {
        return array(
            array(true),
            array(false),
            array(array()),
            array(new stdClass()),
            array(1.234),
        );
    }

    /**
     * @dataProvider hydrateInvalidDatatypeProvider
     */
    public function testHydrateInvalidDatatype($value)
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessageMatches('/^Expected integer or string input, got /');
        $strategy = new \Library\Hydrator\Strategy\Integer();
        $strategy->hydrate($value, null);
    }

    public function hydrateInvalidContentProvider()
    {
        return array(
            array(''),
            array('123a'),
            array('1.234'),
            array('abc'),
        );
    }

    /**
     * @dataProvider hydrateInvalidContentProvider
     */
    public function testHydrateInvalidContent($value)
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Non-integer input value: ' . $value);
        $strategy = new \Library\Hydrator\Strategy\Integer();
        $strategy->hydrate($value, null);
    }

    public function testExtract()
    {
        $strategy = new \Library\Hydrator\Strategy\Integer();
        $this->assertSame(1, $strategy->extract(1));
        $this->assertSame('1', $strategy->extract('1'));
    }
}
