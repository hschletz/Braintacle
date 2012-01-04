<?php
/**
 * Class representing a display device
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
 * @filesource
 */
/**
 * A display device (external monitor, LCD panel...)
 *
 * Properties:
 * - <b>Manufacturer</b>
 * - <b>Description</b>
 * - <b>Serial</b>
 * - <b>ProductionDate</b>
 * - <b>Type</b>
 * @package Models
 */
class Model_Display extends Model_ChildObject
{
    protected $_propertyMap = array(
        // Values from 'monitors' table
        'Manufacturer' => 'manufacturer',
        'Description' => 'caption',
        'Serial' => 'serial',
        'ProductionDate' => 'description',
        'Type' => 'type',
    );
    protected $_tableName = 'monitors';
    protected $_preferredOrder = 'Manufacturer';

}
