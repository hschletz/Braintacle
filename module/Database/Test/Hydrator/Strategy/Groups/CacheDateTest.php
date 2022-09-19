<?php

/**
 * Tests for CacheDate strategy
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

namespace Database\Test\Hydrator\Strategy\Groups;

class CacheDateTest extends \Database\Test\Hydrator\Strategy\AbstractStrategyTest
{
    public function hydrateProvider()
    {
        return array(
            array('', null),
            array('0', null),
            array(0, null),
            array(null, null),
        );
    }

    public function extractProvider()
    {
        return array(
            array(null, 0),
            array(new \DateTime('2015-07-14 20:33:02'), 1436898782),
        );
    }

    public function testHydrateWithoutOffset()
    {
        // testHydrate() cannot compare objects
        $hydrator = new \Database\Hydrator\Strategy\Groups\CacheDate();
        $this->assertEquals(new \DateTime('2015-07-14 20:33:02'), $hydrator->hydrate('1436898782', null));
    }

    public function testHydrateWithOffset()
    {
        $hydrator = new \Database\Hydrator\Strategy\Groups\CacheDate(60);
        $this->assertEquals(new \DateTime('2015-07-14 20:34:02'), $hydrator->hydrate('1436898782', null));
    }

    public function testExtractWithOffset()
    {
        $hydrator = new \Database\Hydrator\Strategy\Groups\CacheDate(60);
        $this->assertEquals('1436898722', $hydrator->extract(new \DateTime('2015-07-14 20:33:02')));
    }
}
