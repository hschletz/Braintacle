<?php
/**
 * Class representing a display controller
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
 * A display controller
 *
 * Properties:
 *
 * - <b>Name</b>
 * - <b>Chipset</b>
 * - <b>Memory</b>
 * - <b>CurrentResolution</b>
 * @package Models
 */
class Model_DisplayController extends Model_ChildObject
{

    /** {@inheritdoc} */
    protected $_propertyMap = array(
        // Values from 'videos' table
        'Name' => 'name',
        'Chipset' => 'chipset',
        'Memory' => 'memory',
        'CurrentResolution' => 'resolution',
    );

    /** {@inheritdoc} */
    protected $_tableName = 'videos';

    /** {@inheritdoc} */
    protected $_preferredOrder = 'Name';

    /**
     * Retrieve a property by its logical name
     *
     * Replaces CurrentResolution of '0 x 0' and Memory of 0 with NULL
     */
    function getProperty($property, $rawValue=false)
    {
        $value = parent::getProperty($property, $rawValue);
        if (!$rawValue) {
            switch ($property) {
                case 'CurrentResolution':
                    if ($value == '0 x 0') {
                        $value = null;
                    }
                    break;
                case 'Memory':
                    if ($value == 0) {
                        $value = null;
                    }
                    break;
            }
        }
        return $value;
    }

}
