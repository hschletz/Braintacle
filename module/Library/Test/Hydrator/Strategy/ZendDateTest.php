<?php
/**
 * Tests for the ZendDate strategy
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

namespace Library\Test\Hydrator\Strategy;

class ZendDateTest extends \PHPUnit_Framework_TestCase
{
    public function testHydrate()
    {
        $strategy = new \Library\Hydrator\Strategy\ZendDate;
        $date = '2015-03-13T15:33:03+01:00';
        $this->assertEquals(new \Zend_Date($date), $strategy->hydrate($date));
    }

    public function testExtract()
    {
        $strategy = new \Library\Hydrator\Strategy\ZendDate;
        $date = '2015-03-13T15:33:03+01:00';
        $this->assertEquals($date, $strategy->extract(new \Zend_Date($date)));
    }
}
