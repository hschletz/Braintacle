<?php

/**
 * Tests for RedirectToRoute controller plugin
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
 * Tests for RedirectToRoute controller plugin
 */
class RedirectToRouteTest extends AbstractTest
{
    /**
     * Invoke the plugin and test the response for expected HTTP redirection
     *
     * Not all possible parameter combinations are tested - this is done by the
     * tests for the underlying UrlFromRoute plugin.
     */
    public function testInvoke()
    {
        $plugin = $this->getPlugin();
        $response = $plugin('testedcontroller', 'testedaction');
        $this->assertInstanceOf('Laminas\Http\Response', $response);
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals(
            '/module/testedcontroller/testedaction/',
            $response->getHeaders()->get('Location')->getFieldValue()
        );
    }
}
