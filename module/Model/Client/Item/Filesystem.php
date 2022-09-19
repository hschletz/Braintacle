<?php

/**
 * Filesystem
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
 * Filesystem
 *
 * @property string $Letter Drive letter (Windows only)
 * @property string $Mountpoint Mountpoint (UNIX only)
 * @property string $Type Device type (CD-Rom Drive | Removable Drive | Hard Drive | Network Drive) (Windows only)
 * @property string $Filesystem Filesystem type
 * @property string $Label Label (Windows only)
 * @property string $Device Device node (UNIX only)
 * @property integer $Size Size in MB
 * @property integer $FreeSpace Free space in MB
 * @property integer $UsedSpace Used space in MB
 * @property \DateTime $CreationDate Date of filesystem creation (UNIX only)
 */
class Filesystem extends \Model\AbstractModel
{
}
