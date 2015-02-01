<?php
/**
 * Class providing access to user defined fields
 *
 * Copyright (C) 2011-2015 Holger Schletz <holger.schletz@web.de>
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
 * User defined fields for a computer
 *
 * The 'TAG' field is always present. Other fields may be defined by the
 * administrator.
 *
 * Field names are case sensitive. To guarantee uniqueness independent from the
 * database implementation, equality checks on field names case insensitive,
 * i.e. a column 'Name' cannnot be added if a column 'name' already exists.
 *
 * When obtaining a list of available fields, the configured order is preserved.
 * @package Models
 */
class Model_UserDefinedInfo extends Model_Abstract
{
    /**
     * Computer this instance is linked to
     *
     * This is set if a computer was passed to the constructor
     * @var Model_Computer
     */
    protected $_computer;

    /**
     * Constructor
     *
     * If a {@link Model_Computer} object is passed, it will be linked to this
     * instance and the data for this computer will be available as properties
     * and for setting via {@link setValues()}.
     * @param Model_Computer $computer
     */
    function __construct(Model_Computer $computer=null)
    {
        parent::__construct();

        $this->_types = \Library\Application::getService('Model\Client\CustomFieldManager')->getFields();
        $this->_propertyMap = \Library\Application::getService('Model\Client\CustomFieldManager')->getColumnMap();

        // Load values if a computer ID is given
        if (!is_null($computer)) {
            $data = Model_Database::getAdapter()
                ->select()
                ->from('accountinfo', array_values($this->_propertyMap))
                ->where('hardware_id = ?', $computer->getId())
                ->query()
                ->fetchObject();
            foreach ($data as $field => $value) {
                $this->$field = $value;
            }

            // Keep track of computer for later updates
            $this->_computer = $computer;
        }
    }

    /**
     * Set the values of user defined fields and store them in the database
     *
     * This method only works if a computer was passed to the constructor.
     * Values not specified in $values will remain unchanged.
     * @param array $values Associative array with the values.
     */
    public function setValues($values)
    {
        if (!$this->_computer) {
            throw new RuntimeException('No Computer was associated with this object');
        }

        $data = array();
        foreach ($values as $property => $value) {
            // Have input processed by setProperty() to ensure valid data and to
            // update the object's internal state
            $this->setProperty($property, $value);
            // Convert dates to DBMS-specific format
            if ($value instanceof Zend_Date) {
                $value = $value->get(
                    Model_Database::getNada()->timestampFormatIso()
                );
            }
            // Build array with column name as key
            $data[$this->_propertyMap[$property]] = $value;
        }

        $db = Model_Database::getAdapter();

        $db->update(
            'accountinfo',
            $data,
            $db->quoteInto('hardware_id = ?', $this->_computer->getId())
        );
    }

    /**
     * Static variant of {@link getPropertyType()}
     * @param string $property Property whose datatype to retrieve
     * @return string Datatype (text, integer, float, date)
     * @deprecated Query CustomFieldManager::getFields() directly
     */
    static function getType($property)
    {
        $types = \Library\Application::getService('Model\Client\CustomFieldManager')->getFields();;
        if (isset($types[$property])) {
            return $types[$property];
        } else {
            throw new UnexpectedValueException('Unknown property: ' . $property);
        }
    }
}
