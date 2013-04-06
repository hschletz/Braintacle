<?php
/**
 * Class representing inventoried registry data
 *
 * Copyright (C) 2011-2013 Holger Schletz <holger.schletz@web.de>
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
 *
 * @package Models
 */
/**
 * Inventoried registry data
 *
 * Properties:
 *
 * - **Value** Name under which the value is stored in the registry configuration
 * - **Data** Registry Data
 *
 * Don't confuse 'Value' with its content. In registry terms, 'Value' refers to
 * the name of the entry that holds the data, while 'Key' is the container that
 * holds the value. While this terminology differs from common usage, it is used
 * throughout the official Windows documentation and API, and Braintacle follows
 * this convention.
 * @package Models
 */
class Model_RegistryData extends Model_ChildObject
{
    /** {@inheritdoc} */
    protected $_propertyMap = array(
        // Values from 'registry' table
        'Value' => 'name',
        'Data' => 'regvalue',
    );

    /** {@inheritdoc} */
    protected $_tableName = 'registry';

    /** {@inheritdoc} */
    protected $_preferredOrder = 'Value';
}
