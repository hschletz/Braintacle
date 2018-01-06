<?php
/**
 * Tests for Platform strategy
 *
 * Copyright (C) 2011-2018 Holger Schletz <holger.schletz@web.de>
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

namespace Database\Test\Hydrator\Strategy\Packages;

class PlatformTest extends \Database\Test\Hydrator\Strategy\AbstractStrategyTest
{
    public function hydrateProvider()
    {
        return array(
            array('WINDOWS', 'windows'),
            array('LINUX', 'linux'),
            array('MacOSX', 'mac'),
        );
    }

    public function extractProvider()
    {
        return array(
            array('windows', 'WINDOWS'),
            array('linux', 'LINUX'),
            array('mac', 'MacOSX'),
        );
    }

    public function testInvalidValues()
    {
        // Suppress notices which are tested separately.
        $this->assertNull(@$this->_strategy->hydrate('invalid'));
        $this->assertNull(@$this->_strategy->extract('invalid'));
    }

    public function testNoticeOnHydrateInvalidValue()
    {
        $this->setExpectedException('PHPUnit_Framework_Error_Notice');
        $this->_strategy->hydrate('invalid');
    }

    public function testNoticeOnExtractInvalidValue()
    {
        $this->setExpectedException('PHPUnit_Framework_Error_Notice');
        $this->_strategy->extract('invalid');
    }
}
