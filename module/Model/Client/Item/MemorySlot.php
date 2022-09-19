<?php

/**
 * RAM slot with details about RAM module if present
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
 * RAM slot with details about RAM module if present
 *
 * @property integer $SlotNumber Slot number, starting with 1
 * @property string $Type RAM type, like 'DDR3'. Possibly inaccurate.
 * @property integer $Size Capacity of installed RAM module, if present.
 * @property string $Clock Clock frequency in MHz. Some systems report additional data. Possibly inaccurate.
 * @property string $Caption Descriptive string
 * @property string $Description Descriptive string
 * @property string $Serial Module's serial number, if available.
 */
class MemorySlot extends \Model\AbstractModel
{
}
