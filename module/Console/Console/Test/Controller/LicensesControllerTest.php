<?php
/**
 * Tests for LicensesController
 *
 * Copyright (C) 2011-2013 Holger Schletz <holger.schletz@web.de>
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
     * Common indexAction() test functionality
     * 
     * @param integer $numManualProductKeys Number of manual product keys to test
     * @param string $path CSS selector to locate $numManualProductKeys in script output
     */
    protected function _testIndexAction($numManualProductKeys, $path)
    {
        $this->reset();
        $model = $this->getMock('Model_Windows');
        $model->expects($this->any())
              ->method('getNumManualProductKeys')
              ->will($this->returnValue($numManualProductKeys));

        $this->getApplicationServiceLocator()
             ->setAllowOverride(true)
             ->setService('Model\Computer\Windows', $model);

        $this->dispatch('/console/licenses/index/');
        $this->assertResponseStatusCode(200);
        $this->assertQueryContentContains($path, "\n$numManualProductKeys\n");

    }

    /**
     * Tests for indexAction()
     */
    public function testIndexAction()
    {
        $this->_testIndexAction(0, 'dd');
        $this->_testIndexAction(1, 'dd a');
    }
}
