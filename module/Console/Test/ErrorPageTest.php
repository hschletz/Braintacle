<?php

/**
 * Tests for the error page
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

namespace Console\Test;

/**
 * Tests for the error page
 */
class ErrorPageTest extends AbstractControllerTest
{
    /**
     * Test 404 case
     */
    public function testPageNotFound()
    {
        $this->dispatch('/invalid');
        $this->assertResponseStatusCode(404);
        $this->assertQueryContentContains('h2', "\nPage not found.\n");
    }

    /**
     * Test routing error
     */
    public function testNoRouteMatch()
    {
        $this->dispatch('/console/group/general/id=42'); // missing '?'
        $this->assertResponseStatusCode(404);
        $this->assertXPathQuery('//p[text()=" No route matched."]');
    }
}
