<?php

/**
 * MS Office Product
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
 * MS Office Product
 *
 * @property string $Name Full product name
 * @property string $Version Marketed version (2007, 2010...)
 * @property string $ExtraDescription Extra description (edition)
 * @property integer $Architecture 32 or 64 (Bit)
 * @property string $ProductId Product ID
 * @property string $ProductKey Product key
 * @property string $Guid Product GUID
 * @property integer $Type TYPE_INSTALLED_PRODUCT or TYPE_UNUSED_LICENSE
 */
class MsOfficeProduct extends \Model\AbstractModel
{
    /**
     * "Type" property for an unused license (leftover from an uninstalled product)
     **/
    const TYPE_UNUSED_LICENSE = 0;

    /**
     * "Type" property for a regular installed product
     **/
    const TYPE_INSTALLED_PRODUCT = 1;
}
