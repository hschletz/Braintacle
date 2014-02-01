<?php
/**
 * Class representing an audio device
 *
 * Copyright (C) 2011-2014 Holger Schletz <holger.schletz@web.de>
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
 * An audio device
 *
 * Properties:
 *
 * - <b>Manufacturer</b>
 * - <b>Name</b>
 * - <b>Description</b>
 * @package Models
 */
class Model_AudioDevice extends Model_ChildObject
{

    /** {@inheritdoc} */
    protected $_propertyMap = array(
        // Values from 'sounds' table
        'Manufacturer' => 'manufacturer',
        'Name' => 'name',
        'Description' => 'description',
    );

    /** {@inheritdoc} */
    protected $_tableName = 'sounds';

    /** {@inheritdoc} */
    protected $_preferredOrder = 'Manufacturer';

}
