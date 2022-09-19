<?php

/**
 * NetworkInterface item plugin
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

namespace Model\Client\Plugin;

/**
 * NetworkInterface item plugin
 *
 * Adds boolean "is_blacklisted" column to indicate MAC address ignored for
 * duplicates search.
 */
class NetworkInterface extends DefaultPlugin
{
    /** {@inheritdoc} */
    public function join()
    {
        $this->_select->join(
            'blacklist_macaddresses',
            'macaddress = macaddr',
            array('is_blacklisted' => new \Laminas\Db\Sql\Literal('(blacklist_macaddresses.macaddress IS NOT NULL)')),
            \Laminas\Db\Sql\Select::JOIN_LEFT
        );
    }
}
