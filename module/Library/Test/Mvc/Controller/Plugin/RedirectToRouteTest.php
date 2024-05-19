<?php

/**
 * Tests for RedirectToRoute controller plugin
 *
 * Copyright (C) 2011-2024 Holger Schletz <holger.schletz@web.de>
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

use Laminas\Http\Response;
use Laminas\Mvc\Controller\Plugin\Redirect as RedirectPlugin;
use Library\Mvc\Controller\Plugin\RedirectToRoute as RedirectToRoutePlugin;
use Library\Mvc\Controller\Plugin\UrlFromRoute as UrlFromRoutePlugin;

/**
 * Tests for RedirectToRoute controller plugin
 */
class RedirectToRouteTest extends AbstractTestCase
{
    public function testInvoke()
    {
        $response = new Response();

        $redirectPlugin = $this->createMock(RedirectPlugin::class);
        $redirectPlugin->method('toUrl')->with('url')->willReturn($response);

        $urlFromRoutePlugin = $this->createMock(UrlFromRoutePlugin::class);
        $urlFromRoutePlugin->method('__invoke')->with('controller', 'action', ['key' => 'value'])->willReturn('url');

        $redirectToRoutePlugin = new RedirectToRoutePlugin($redirectPlugin, $urlFromRoutePlugin);

        $this->assertSame($response, $redirectToRoutePlugin('controller', 'action', ['key' => 'value']));
    }
}
