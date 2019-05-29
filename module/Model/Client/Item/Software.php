<?php
/**
 * Installed software
 *
 * Copyright (C) 2011-2019 Holger Schletz <holger.schletz@web.de>
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
 * Installed software
 *
 * @property string $Name Name
 * @property string $Version Version
 * @property string $Comment Comment
 * @property string $Publisher Publisher/Manufacturer (Windows only)
 * @property string $InstallLocation Installation directory (Windows only)
 * @property bool $IsHotfix TRUE for Windows hotfixes (Windows only)
 * @property string $Guid GUID - may contain the MSI GIUD or arbitrary string (Windows only)
 * @property string $Language UI Language (Windows only)
 * @property \DateTime $InstallationDate Date of installation (Windows only)
 * @property integer $Architecture 32/64/NULL (Windows only)
 * @property integer $Size Package size (Unix only)
 */
class Software extends \ArrayObject
{
}
