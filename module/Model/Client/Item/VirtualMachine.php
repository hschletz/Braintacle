<?php

/**
 * Virtual machine hosted on a client
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
 * Virtual machine hosted on a client
 *
 * @property string $Name VM Name
 * @property string $Status Status at inventory time
 * @property string $Product Virtualization product
 * @property string $Type VM type (some types are supported by different products)
 * @property string $Uuid UUID
 * @property integer $NumCpus Number of guest CPUs (unreliable)
 * @property integer $GuestMemory Guest RAM in MB (unreliable)
 */
class VirtualMachine extends \Model\AbstractModel
{
}
