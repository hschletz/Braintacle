<?php

/**
 * Installed software
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

use DateTimeInterface;

/**
 * Installed software
 */
class Software
{
    /**
     * Name
     */
    public string $name;

    /**
     * Version
     */
    public ?string $version;

    /**
     * Comment (Windows/UNIX only)
     */
    public ?string $comment;

    /**
     * Publisher/Manufacturer (Windows/Android only)
     */
    public ?string $publisher;

    /**
     * Installation directory (Windows/Android only)
     */
    public ?string $installLocation;

    /**
     * TRUE for Windows hotfixes (Windows only)
     */
    public ?bool $isHotfix;

    /**
     * GUID - may contain the MSI GIUD or arbitrary string (Windows only)
     */
    public ?string $guid;

    /**
     * UI Language (Windows only)
     */
    public ?string $language;

    /**
     * Date of installation (Windows only)
     */
    public ?DateTimeInterface $installationDate;

    /**
     * Architecture: 32/64 (Windows only)
     */
    public ?int $architecture;

    /**
     * Package size (UNIX only)
     */
    public ?int $size;
}
