<?php

/**
 * Network interface
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

namespace Model\Client\Item;

/**
 * Network interface
 *
 * @property string $Description
 * @property string $Rate Link transfer rate including unit (Mb/s, Gb/s...)
 * @property \Library\MacAddress $MacAddress
 * @property string $IpAddress
 * @property string $Netmask
 * @property string $Gateway
 * @property string $Subnet Network address
 * @property string $DhcpServer IP address of DHCP server
 * @property string $Status Up|Down
 * @property string $IsBlacklisted TRUE if the MAC address is blacklisted for detection of duplicates
 * @property string $Type needs further processing to be useful
 * @property string $TypeMib needs further processing to be useful
 */
class NetworkInterface extends \Model\AbstractModel
{
}
