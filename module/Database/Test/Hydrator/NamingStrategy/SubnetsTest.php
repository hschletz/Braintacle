<?php
/**
 * Tests for Subnets naming strategy
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

namespace Database\Test\Hydrator\NamingStrategy;

class Subnets extends AbstractNamingStrategyTest
{
    public function hydrateProvider()
    {
        return array(
            array('netid', 'Address'),
            array('mask', 'Mask'),
            array('name', 'Name'),
            array('num_inventoried', 'NumInventoried'),
            array('num_identified', 'NumIdentified'),
            array('num_unknown', 'NumUnknown'),
        );
    }

    public function extractProvider()
    {
        return array(
            array('Address', 'netid'),
            array('Mask', 'mask'),
            array('Name', 'name'),
            array('NumInventoried', 'num_inventoried'),
            array('NumIdentified', 'num_identified'),
            array('NumUnknown', 'num_unknown'),
        );
    }
}
