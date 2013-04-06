<?php
/**
 * Class representing the association of a package to a computer
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
 * Package association
 *
 * Properties:
 *
 * - <b>Name</b> Package name
 * - <b>Status</b> Status on this computer
 * @package Models
 */
class Model_PackageAssignment extends Model_ChildObject
{

    /** {@inheritdoc} */
    protected $_propertyMap = array(
        // Values from query result
        'Computer' => 'hardware_id',
        'Name' => 'name',
        'Status' => 'tvalue',
        'Timestamp' => 'comments'
    );

    /** {@inheritdoc} */
    protected $_types = array(
        'Timestamp' => 'timestamp',
    );

    /**
     * Return a statement|select object with all objects matching criteria.
     *
     * This implementation ignores $columns and always returns all properties.
     */
    public function createStatement(
        $columns=null,
        $order=null,
        $direction='asc',
        $filters=null,
        $query=true
    )
    {
        $db = Model_Database::getAdapter();

        if (is_null($order)) {
            $order = 'Name';
        }
        $order = self::getOrder($order, $direction, $this->_propertyMap);

        $select = $db->select()
            ->from('devices', array('hardware_id', 'tvalue', 'comments'))
            ->join(
                'download_enable',
                'devices.ivalue=download_enable.id',
                array()
            )
            ->join(
                'download_available',
                'download_enable.fileid=download_available.fileid',
                array('name')
            )
            ->where("devices.name='DOWNLOAD'")
            ->order($order);

        if (!is_null($filters) and isset($filters['Computer'])) {
            $select->where('hardware_id = ?', (int) $filters['Computer']);
        }

        if ($query) {
            return $select->query();
        } else {
            return $select;
        }
    }

    /**
     * Return the name of the table which stores this object.
     *
     * This class does not operate on a single table and therefore throws an
     * exception.
     */
    public function getTableName()
    {
        throw (
            new ErrorException(
                'getTableName() can not be called on Model_PackageAssignment'
            )
        );
    }

    /**
     * Retrieve a property by its logical name
     *
     * Converts the timestamp from the internal format to ISO.
     */
    public function getProperty($property, $rawValue=false)
    {
        if ($rawValue or $property != 'Timestamp') {
            return parent::getProperty($property, $rawValue);
        }

        $value = parent::getProperty('Timestamp', true);
        if (empty($value)) {
            return null;
        }

        // Example: "Tue Feb  2 13:44:23 2010"
        $date = new Zend_Date;
        $date->setTimezone('UTC'); // prevents altering of $value by DST calculations
        $months = array(
            'Jan',
            'Feb',
            'Mar',
            'Apr',
            'May',
            'Jun',
            'Jul',
            'Aug',
            'Sep',
            'Oct',
            'Nov',
            'Dec'
        );
        $month = array_search(substr($value, 4, 3), $months);
        if ($month === false) {
            throw new UnexpectedValueException(
                'Invalid month in timestamp: ' . $value
            );
        }
        $value = array(
            'year' => substr($value, 20, 4),
            'month' => $month + 1,
            'day' => substr($value, 8, 2),
            'hour' => substr($value, 11, 2),
            'minute' => substr($value, 14, 2),
            'second' => substr($value, 17, 2),
        );
        $date->set($value);
        return $date;
    }

}
