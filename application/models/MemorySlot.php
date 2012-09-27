<?php
/**
 * Class representing a RAM slot
 *
 * $Id$
 *
 * Copyright (C) 2011,2012 Holger Schletz <holger.schletz@web.de>
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
 * A single RAM slot. If a module is present, details are given.
 *
 * Properties:
 *
 * - <b>SlotNumber</b> Number of the slot, starting with 1. For every physical slot, empty or not, there is 1 object.
 * - <b>Size</b> Capacity of installed RAM module, if present.
 * - <b>Type</b> RAM type, like 'SDRAM'. Not necessarily accurate, don't rely on it.
 * - <b>Clock</b> Clock frequency in MHz. Some systems report incorrect values.
 * - <b>Serial</b> Module's serial number, if available.
 * - <b>Caption</b> Some stuff.
 * - <b>Description</b> More stuff, sometimes meaningful.
 * - <b>Purpose</b> More stuff.
 * @package Models
 */
class Model_MemorySlot extends Model_ChildObject
{

    /** {@inheritdoc} */
    protected $_propertyMap = array(
        // Values from 'memories' table
        'SlotNumber' => 'numslots',
        'Size' => 'capacity',
        'Type' => 'type',
        'Clock' => 'speed',
        'Serial' => 'serialnumber',
        'Caption' => 'caption',
        'Description' => 'description',
        'Purpose' => 'purpose',
    );

    /** {@inheritdoc} */
    protected $_types = array(
        'SlotNumber' => 'integer',
    );

    /** {@inheritdoc} */
    protected $_tableName = 'memories';

    /** {@inheritdoc} */
    protected $_preferredOrder = 'SlotNumber';

    /**
     * Retrieve a property by its logical name.
     * Corrects some strange values for 'Size', which are now guaranteed to be numeric.
     * @param string $property Logical property name
     * @param bool $rawValue If TRUE, do not process the value. Default: FALSE
     * @return mixed Property value. Derived class may have processed the value.
     */
    public function getProperty($property, $rawValue=false)
    {
        $value = parent::getProperty($property, $rawValue);

         // Some agents set fancy string values instead of NULL or 0.
        if (!$rawValue and $property == 'Size' and !is_numeric($value)) {
            $value = 0;
        }

        return $value;
    }

}
