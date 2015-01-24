<?php
/**
 * Tests for LicensesController
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

namespace Console\Test\Controller;

/**
 * Tests for LicensesController
 */
class LicensesControllerTest extends \Console\Test\AbstractControllerTest
{
    /**
     * Windows mock
     * @var \Model_Windows
     */
    protected $_windows;

    /** {@inheritdoc} */
    protected function _createController()
    {
        return new \Console\Controller\LicensesController($this->_windows);
    }

    /**
     * Tests for indexAction()
     */
    public function testIndexAction()
    {
        $url = '/console/licenses/index/';

        // Zero manual product keys produce <dd>0</dd>.
        $this->_windows = $this->getMock('Model_Windows');
        $this->_windows->expects($this->any())
                       ->method('getNumManualProductKeys')
                       ->will($this->returnValue(0));
        $this->dispatch($url);
        $this->assertResponseStatusCode(200);
        $this->assertQueryContentContains('dd', "\n0\n");

        // Nonzero manual product keys produce <dd><a...>n</a></dd>.
        $this->_windows = $this->getMock('Model_Windows');
        $this->_windows->expects($this->any())
                       ->method('getNumManualProductKeys')
                       ->will($this->returnValue(1));
        $this->dispatch($url);
        $this->assertResponseStatusCode(200);
        $this->assertXPathQueryContentContains(
            '//dd/a[@href="/console/client/index/?' .
            'columns=Name,OsName,Windows.ProductKey,Windows.ManualProductKey&' .
            'filter=Windows.ManualProductKey&order=Name&direction=asc"]',
            "\n1\n"
        );
    }
}
