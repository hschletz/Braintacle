<?php
/**
 * Class representing Windows-specific information for a computer
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
 * Windows-specific information for a computer
 *
 * Properties:
 *
 * - <b>ComputerId:</b> ID of computer this instance belongs to
 * - <b>UserDomain:</b> Domain of logged in user (for local accounts this is identical to the computer name)
 * - <b>Company:</b> Company name (typed in at installation)
 * - <b>Owner:</b> Owner (typed in at installation)
 * - <b>ProductKey:</b> Product Key (aka license key, typed in at installation)
 * - <b>ProductId:</b> Product ID (installation-specific, may or may not be unique)
 * - <b>ManualProductKey:</b> Manually overridden product key (entered in Braintacle console)
 * @package Models
 */
class Model_Windows extends Model_Abstract
{

    /** {@inheritdoc} */
    protected $_propertyMap = array(
        // Values from 'hardware' table
        'ComputerId' => 'id',
        'UserDomain' => 'userdomain',
        'Company' => 'wincompany',
        'Owner' => 'winowner',
        'ProductKey' => 'winprodkey',
        'ProductId' => 'winprodid',
        // Values from 'braintacle_windows' table
        'ManualProductKey' => 'manual_product_key',
    );

    /** {@inheritdoc} */
    public function setProperty($property, $value)
    {
        if ($property == 'ManualProductKey') {
            // Validate and store value in database
            if (empty($value) or $value == $this->getProductKey()) {
                $value = null;
            } else {
                $validator = new \Library\Validator\ProductKey;
                if (!$validator->isValid($value)) {
                    throw new UnexpectedValueException(current($validator->getMessages()));
                }
            }

            $db = Model_Database::getAdapter();
            // A record might not exist yet, so try UPDATE first, then INSERT
            if (!$db->update(
                'braintacle_windows',
                array('manual_product_key' => $value),
                array('hardware_id = ?' => $this->getComputerId())
            )) {
                $db->insert(
                    'braintacle_windows',
                    array(
                        'hardware_id' => $this->getComputerId(),
                        'manual_product_key' => $value,
                    )
                );
            }
        }

        parent::setProperty($property, $value);
    }

    /**
     * Get name of table where a property is stored
     *
     * @param string $property Property name
     * @return string Table name
     * @throws UnexpectedValueException if $property is invalid
     **/
    public static function getTableName($property)
    {
        if ($property == 'ManualProductKey') {
            return 'braintacle_windows';
        } else {
            $dummy = new Model_Windows;
            if (isset($dummy->_propertyMap[$property])) {
                return 'hardware';
            } else {
                throw new UnexpectedValueException('Unknown property: ' . $property);
            }
        }
    }

    /**
     * Create Model_Windows object for given computer
     *
     * It is valid to pass a non-Windows computer object in which case the
     * content of the returned object is undefined.
     * @param Model_Computer $computer
     * @return Model_Windows
     * @throws UnexpectedValueException if $computer is invalid
     **/
    public static function getWindows($computer)
    {
        $select = Model_Database::getAdapter()
                  ->select()
                  ->from('hardware', array('id, userdomain, wincompany, winowner, winprodkey, winprodid'))
                  ->where('id = ?', $computer->getId());
        if (Model_Database::supportsManualProductKey()) {
            $select->joinLeft(
                'braintacle_windows',
                'hardware.id = braintacle_windows.hardware_id',
                array('manual_product_key')
            );
        }
        $windows = $select->query()->fetchObject(__CLASS__);
        if (!$windows) {
            throw new UnexpectedValueException('Invalid computer ID: ' . $computer->getId());
        }
        return $windows;
    }

    /**
     * Get number of computers with manually entered Windows product key
     * @return integer
     **/
    public function getNumManualProductKeys()
    {
        if (Model_Database::supportsManualProductKey()) {
            return Model_Database::getAdapter()->fetchOne(
                'SELECT COUNT(manual_product_key) FROM braintacle_windows WHERE manual_product_key IS NOT NULL'
            );
        } else {
            return 0;
        }
    }

}
