<?php
/**
 * Tests for Packages naming strategy
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

class Packages extends AbstractNamingStrategyTest
{
    public function hydrateProvider()
    {
        return array(
            array('name', 'Name'),
            array('fileid', 'Id'),
            array('priority', 'Priority'),
            array('fragments', 'NumFragments'),
            array('size', 'Size'),
            array('osname', 'Platform'),
            array('comment', 'Comment'),
            array('num_nonnotified', 'NumNonnotified'),
            array('num_success', 'NumSuccess'),
            array('num_notified', 'NumNotified'),
            array('num_error', 'NumError'),
        );
    }

    public function extractProvider()
    {
        return array(
            array('Name', 'name'),
            array('Id', 'fileid'),
            array('Priority', 'priority'),
            array('NumFragments', 'fragments'),
            array('Size', 'size'),
            array('Platform', 'osname'),
            array('Comment', 'comment'),
            array('Timestamp', 'fileid'),
            array('NumNonnotified', 'num_nonnotified'),
            array('NumSuccess', 'num_success'),
            array('NumNotified', 'num_notified'),
            array('NumError', 'num_error'),
        );
    }
}
