<?php

/**
 * Tests for InventoryDecode filter
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

namespace Protocol\Test\Filter;

class InventoryDecodeTest extends \PHPUnit\Framework\TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf('Laminas\Filter\AbstractFilter', new \Protocol\Filter\InventoryDecode());
    }

    public function filterProvider()
    {
        return array(
            array('small'),
            array('large'),
        );
    }

    /**
     * @dataProvider filterProvider
     */
    public function testFilter($suffix)
    {
        $output = \Laminas\Filter\StaticFilter::execute(
            file_get_contents(
                \Protocol\Module::getPath("data/Test/Filter/InventoryDecode/encoded-$suffix")
            ),
            'Protocol\InventoryDecode'
        );
        $this->assertNotEmpty($output);
        $this->assertEquals(
            file_get_contents(
                \Protocol\Module::getPath("data/Test/Filter/InventoryDecode/decoded-$suffix")
            ),
            $output
        );
    }

    public function testFilterInvalidInput()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Input does not appear to be a zlib stream');
        \Laminas\Filter\StaticFilter::execute('not a zlib stream', 'Protocol\InventoryDecode');
    }
}
