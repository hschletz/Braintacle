<?php
/**
 * Class representing a port connector
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
 * A port (Centronics, RS-232 and similar)
 *
 * Properties:
 *
 * - <b>Type</b>
 * - <b>Name</b>
 * - <b>Connector</b> Connector type (UNIX only), just a duplicate of Name on Windows
 * @package Models
 */
class Model_Port extends Model_ChildObject
{
    /** {@inheritdoc} */
    protected $_propertyMap = array(
        // Values from 'ports' table
        'Name' => 'name',
        'Type' => 'type',
        'Connector' => 'caption',
        'RawDescription' => 'description' // Useless, identical to Name without the port name
    );

    /** {@inheritdoc} */
    protected $_tableName = 'ports';

    /** {@inheritdoc} */
    protected $_preferredOrder = 'Name';

}
