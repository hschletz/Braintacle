<?php
/**
 * Tests for RegistryValueDefinitions naming strategy
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

class RegistryValueDefinitions extends AbstractNamingStrategyTest
{
    public function hydrateProvider()
    {
        return array(
            array('id', 'Id'),
            array('name', 'Name'),
            array('regtree', 'RootKey'),
            array('regkey', 'SubKeys'),
            array('regvalue', 'ValueConfigured'),
        );
    }

    public function extractProvider()
    {
        return array(
            array('Id', 'id'),
            array('Name', 'name'),
            array('RootKey', 'regtree'),
            array('SubKeys', 'regkey'),
            array('ValueConfigured', 'regvalue'),
        );
    }
}
