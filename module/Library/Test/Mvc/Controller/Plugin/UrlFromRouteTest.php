<?php

/**
 * Tests for UrlFromRoute controller plugin
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

namespace Library\Test\Mvc\Controller\Plugin;

/**
 * Tests for UrlFromRoute controller plugin
 */
class UrlFromRouteTest extends AbstractTest
{
    /**
     * Tests combinations of default/given arguments, query parameters and
     * encoding.
     */
    public function testInvoke()
    {
        $plugin = $this->getPlugin();

        $this->assertEquals(
            '/module/defaultcontroller/defaultaction/',
            $plugin(null, null)
        );

        $this->assertEquals(
            '/module/defaultcontroller/testedaction/',
            $plugin(null, 'testedaction')
        );

        $this->assertEquals(
            '/module/testedcontroller/defaultaction/',
            $plugin('testedcontroller', null)
        );

        $this->assertEquals(
            '/module/testedcontroller/testedaction/',
            $plugin('testedcontroller', 'testedaction')
        );

        $this->assertEquals(
            '/module/tested%2Fcontroller/tested%2Faction/?tested/key=tested/value&tested%26key=tested%26value',
            $plugin(
                'tested/controller',
                'tested/action',
                array(
                    'tested/key' => 'tested/value', // not encoded
                    'tested&key' => 'tested&value', // encoded to %26
                )
            )
        );
    }
}
