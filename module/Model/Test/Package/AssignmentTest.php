<?php

/**
 * Tests for Model\Package\Assignment
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

namespace Model\Test\Package;

use DateTime;
use Model\Package\Assignment;
use PHPUnit\DbUnit\DataSet\DefaultDataSet;

/**
 * Tests for Model\Package\Assignment
 */
class AssignmentTest extends \Model\Test\AbstractTest
{
    public function getDataSet()
    {
        return new DefaultDataSet();
    }

    public function testDateFormat()
    {
        $date = new DateTime('2014-12-30 19:01:23');
        $this->assertEquals('Tue Dec 30 19:01:23 2014', $date->format(Assignment::DATEFORMAT));
        $this->assertEquals(
            $date,
            DateTime::createFromFormat(Assignment::DATEFORMAT, 'Tue Dec 30 19:01:23 2014')
        );

        $date = new DateTime('2014-03-01 09:01:03');
        $this->assertEquals('Sat Mar 01 09:01:03 2014', $date->format(Assignment::DATEFORMAT));
        $this->assertEquals(
            $date,
            DateTime::createFromFormat(Assignment::DATEFORMAT, 'Sat Mar 01 09:01:03 2014')
        );
        $this->assertEquals(
            $date,
            DateTime::createFromFormat(Assignment::DATEFORMAT, 'Sat Mar  1 09:01:03 2014')
        );
    }
}
