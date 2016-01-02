<?php
/**
 * Storage device (hard disk, optical drive, USB storage...)
 *
 * Copyright (C) 2011-2016 Holger Schletz <holger.schletz@web.de>
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
 * Storage device (hard disk, optical drive, USB storage...)
 *
 * @property string $Type Hard disk, DVD writer... (Windows only)
 * @property string $ProductFamily Manufacturer/Series (UNIX only)
 * @property string $Model Model description (supplied by manufacturer)
 * @property string $Device Windows: Device path (ex. "//./PHYSICALDRIVE0") for hard disks, UNIX: device node
 * @property integer $Size Size in MB
 * @property string $Serial Serial number
 * @property string $Firmware Firmware version
 */
class StorageDevice extends \ArrayObject
{
}
