<?php
/**
 * Class representing an inventoried registry key
 *
 * $Id$
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
 * An inventoried registry key
 *
 * Properties:
 *
 * - <b>Name</b> Name under which the key is stored in the registry configuration
 * - <b>Value</b> Registry value
 * @package Models
 */
class Model_Registry extends Model_ChildObject
{
    /** {@inheritdoc} */
    protected $_propertyMap = array(
        // Values from 'registry' table
        'Name' => 'name',
        'Value' => 'regvalue',
    );

    /** {@inheritdoc} */
    protected $_tableName = 'registry';

    /** {@inheritdoc} */
    protected $_preferredOrder = 'Name';
}
