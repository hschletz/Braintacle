<?php
/**
 * Class representing a storage volume
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
 * A volume (partition with filesystem, removable drive, network share...)
 *
 * Properties:
 * - <b>Letter</b> Drive letter, like C: (Windows only)
 * - <b>Label</b> Filesystem label (Windows only)
 * - <b>Type</b> Type (partition, removable drive, network share...) (Windows only)
 * - <b>Device</b> Device node (UNIX only)
 * - <b>Mountpoint</b> Mountpoint (UNIX only)
 * - <b>Filesystem</b> Filesystem type
 * - <b>Size</b> Size in MB
 * - <b>FreeSpace</b> Free space in MB
 * - <b>UsedSpace</b> Used space in MB
 * - <b>CreationDate</b> Date of filesystem creation (UNIX only)
 * @package Models
 */
class Model_Volume extends Model_ChildObject
{
    protected $_propertyMap = array(
        // Values from 'drives' table. Some properties refer to the same column, depending on OS.
        'Letter' => 'letter',
        'Label' => 'volumn',
        'Device' => 'volumn',
        'Type' => 'type',
        'Mountpoint' => 'type',
        'Filesystem' => 'filesystem',
        'Size' => 'total',
        'FreeSpace' => 'free',
        'CreationDate' => 'createdate',
        // UsedSpace is calculated by getProperty()
    );

    protected $_types = array(
        'Size' => 'integer',
        'FreeSpace' => 'integer',
        'CreationDate' => 'date',
        'UsedSpace' => 'integer',
    );

    protected $_tableName = 'drives';
    protected $_preferredOrder = 'Letter';

    /**
     * Retrieve a property by its logical name
     *
     * Adds UsedSpace property and strips trailing slash from Letter.
     */
    function getProperty($property, $rawValue=false)
    {
        if ($property == 'UsedSpace') {
            return $this->getSize() - $this->getFreeSpace();
        } else {
            $value = parent::getProperty($property, $rawValue);
            if ($property == 'Letter' and !$rawValue) {
                // strip trailing slash
                $value = preg_replace('/\/$/', '', $value);
            }
            return $value;
        }
    }

    /**
     * Return the datatypes of all properties
     *
     * Add types of calculated properties that are not part of the property map.
     */
    public function getPropertyTypes()
    {
        if (empty($this->_allTypes)) { // build _allTypes only once
            parent::getPropertyTypes();
            $this->_allTypes['UsedSpace'] = 'integer';
        }
        return $this->_allTypes;
    }

}
