<?php
/**
 * Tests for Import controller
 *
 * Copyright (C) 2011-2021 Holger Schletz <holger.schletz@web.de>
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

namespace Tools\Test\Controller;

class ImportTest extends AbstractControllerTest
{
    /**
     * Client manager mock
     * @var \Model\Client\ClientManager
     */
    protected $_clientManager;

    public function setUp(): void
    {
        parent::setUp();
        $this->_clientManager = $this->createMock('Model\Client\ClientManager');
        static::$serviceManager->setService('Model\Client\ClientManager', $this->_clientManager);
    }

    public function testSuccess()
    {
        $this->_clientManager->expects($this->once())->method('importFile')->with('input file');
        $this->_route->method('getMatchedParam')->with('filename')->willReturn('input file');
        $this->assertEquals(0, $this->_dispatch());
    }
}
