<?php
/**
 * Class representing a storage device
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
 * A storage device (Hard disk, optical drive...)
 *
 * Properties:
 * - <b>Type</b> Type
 * - <b>Name</b> Name
 * - <b>Size</b> Size in MB
 * - <b>Device</b> Name of device node (UNIX only)
 * - <b>Serial</b> Serial number (UNIX only)
 * - <b>Firmware</b> Firmware version (UNIX only)
 * @package Models
 */
class Model_StorageDevice extends Model_ChildObject
{
    protected $_propertyMap = array(
        // Values from 'storages' table. The 'Raw' properties are for internal use only.
        'RawComputer' => 'hardware_id',
        'RawType' => 'type',
        'RawName' => 'name',
        'RawDescription' => 'description',
        'RawManufacturer' => 'manufacturer',
        'RawModel' => 'model',
        'Size' => 'disksize',
        'Serial' => 'serialnumber',
        'Firmware' => 'firmware',
        'Device' => 'name',
    );

    protected $_types = array(
        'RawComputer' => 'integer',
        'Size' => 'integer',
    );

    protected $_tableName = 'storages';
    protected $_preferredOrder = 'RawType';

    /**
     * Retrieve a property by its logical name
     *
     * Generates properties from different sources depending on OS.
     */
    function getProperty($property, $rawValue=false)
    {
        if ($rawValue
            or $property == 'Size'
            or $property == 'Serial'
            or $property == 'Firmware'
            or strpos($property, 'Raw') === 0
        ) {
            return parent::getProperty($property, $rawValue);
        }

        $computer = Model_Computer::fetchById($this->getRawComputer());
        if (!$computer) {
            throw new RuntimeException(
                sprintf('No computer found with ID %d', $this->getRawComputer())
            );
        }
        if ($computer->isWindows()) {
            switch ($property) {
                case 'Type':
                    $translate = Zend_Registry::get('Zend_Translate');

                    switch (substr($this->getRawType(), 0, 5)) {
                        case 'Fixed':
                            $value = $translate->_('Hard disk');
                            break;
                        case 'Remov':
                            $value = $translate->_('Removable media');
                            break;
                        default:
                            $value = $this->getRawDescription();
                    }
                    break;
                case 'Name':
                    $value = $this->getRawName();
                    break;
                case 'Device':
                    $value = null;
                    break;
            }
        } else {
            switch ($property) {
                case 'Type':
                    $value = $this->getRawDescription() . ' ' . $this->getRawType();
                    break;
                case 'Name':
                    $value = $this->getRawManufacturer() . ' ' . $this->getRawModel();
                    break;
                case 'Device':
                    $value = $this->getRawName();
                    break;
            }
        }
        return $value;
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
            $this->_allTypes['Type'] = 'text';
            $this->_allTypes['Name'] = 'text';
        }
        return $this->_allTypes;
    }

}
