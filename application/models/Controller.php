<?php
/**
 * Class representing a piece of controller hardware
 *
 * $Id$
 *
 * Copyright (C) 2011 Holger Schletz <holger.schletz@web.de>
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
 * A controller (IDE, SCSI, Floppy, USB, FireWire, Infrared, PCMCIA...)
 *
 * Properties:
 * - <b>Type</b>
 * - <b>Manufacturer</b>
 * - <b>Name</b>
 * - <b>Comment</b> In most cases identical to Name
 * @package Models
 */
class Model_Controller extends Model_ChildObject
{
    protected $_propertyMap = array(
        // Values from 'controllers' table
        'Type' => 'type',
        'Manufacturer' => 'manufacturer',
        'Name' => 'name',
        'Comment' => 'description',
    );
    protected $_xmlElementName = 'CONTROLLERS';
    protected $_xmlElementMap = array(
        'CAPTION' => null,
        'DESCRIPTION' => 'Comment',
        'DRIVER' => null,
        'MANUFACTURER' => 'Manufacturer',
        'NAME' => 'Name',
        'PCIID' => null,
        'PCISLOT' => null,
        'TYPE' => 'Type',
        'VERSION' => null,
    );
    protected $_tableName = 'controllers';
    protected $_preferredOrder = 'Type';

}
