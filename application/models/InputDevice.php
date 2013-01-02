<?php
/**
 * Class representing an input device
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
 * An input device (Keyboard, mouse, touchpad...)
 *
 * Properties:
 *
 * - <b>Type</b>
 * - <b>Manufacturer</b>
 * - <b>Description</b>
 * - <b>Comment</b>
 * - <b>Interface</b>
 * @package Models
 */
class Model_InputDevice extends Model_ChildObject
{

    /** {@inheritdoc} */
    protected $_propertyMap = array(
        // Values from 'inputs' table
        'Type' => 'type',
        'Manufacturer' => 'manufacturer',
        'Description' => 'caption',
        'Comment' => 'description',
        'Interface' => 'interface',
        'RawPointType' => 'pointtype' // No useful information, always 'N/A' or NULL
    );

    /** {@inheritdoc} */
    protected $_tableName = 'inputs';

    /** {@inheritdoc} */
    protected $_preferredOrder = 'Type';

}
