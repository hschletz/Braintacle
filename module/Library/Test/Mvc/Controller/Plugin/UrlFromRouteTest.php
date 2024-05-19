<?php

/**
 * Tests for UrlFromRoute controller plugin
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

use Laminas\Mvc\Controller\Plugin\Url as UrlPlugin;
use Library\Mvc\Controller\Plugin\UrlFromRoute as UrlFromRoutePlugin;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests for UrlFromRoute controller plugin
 */
class UrlFromRouteTest extends AbstractTestCase
{
    public static function invokeProvider()
    {
        return [
            [null, null, [], []],
            [null, '_action', [], ['action' => '_action']],
            ['_controller', null, [], ['controller' => '_controller']],
            ['_controller', '_action', [], ['controller' => '_controller', 'action' => '_action']],
            ['con/troller', 'ac/tion', [], ['controller' => 'con%2Ftroller', 'action' => 'ac%2Ftion']],
            [null, null, ['key' => 'value'], []],
        ];
    }

    #[DataProvider('invokeProvider')]
    public function testInvoke(?string $controller, ?string $action, array $query, array $expectedParams)
    {
        $urlPlugin = $this->createMock(UrlPlugin::class);
        $urlPlugin->method('fromRoute')->with(
            null,
            $expectedParams,
            ['query' => $query],
        )->willReturn('url');

        $urlFromRoutePlugin = new UrlFromRoutePlugin($urlPlugin);
        $this->assertEquals('url', $urlFromRoutePlugin($controller, $action, $query));
    }
}
