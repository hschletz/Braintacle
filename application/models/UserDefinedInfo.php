<?php
/**
 * Class providing access to user defined fields
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
 * User defined fields for a computer
 *
 * The 'tag' field is always present. Other fields may be defined by the
 * administrator. Their names are always returned lowercase.
 * @package Models
 */
class Model_UserDefinedInfo extends Model_Abstract
{

    /**
     * Datatypes of all properties.
     *
     * Initially empty, typically managed by {@link getTypes()}.
     * Do not use it directly - always call getTypes() or getPropertyTypes().
     * @var array
     */
    static protected $_allTypesStatic = array();

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

        // Construct array of datatypes
        $this->_types = self::getTypes();

        // Construct property map. Key and value are identical.
        foreach ($this->_types as $name => $type) {
            $this->_propertyMap[$name] = $name;
        }

        // Load values if a computer ID is given
        if (!is_null($computer)) {
            $db = Model_Database::getAdapter();

            $data = $db->fetchRow(
                'SELECT * FROM accountinfo WHERE hardware_id = ?',
                $computer->getId()
            );
            foreach ($data as $property => $value) {
                if (isset($this->_propertyMap[$property])) { // ignore hardware_id and BLOB columns
                    $this->setProperty($property, $value);
                }
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

        foreach ($values as $property => $value) {
            // Have input processed by setProperty() to ensure valid data and to
            // update the object's internal state
            $this->setProperty($property, $value);
            // Convert dates
            if ($value instanceof Zend_Date) {
                $values[$property] = $value->get(
                    Model_Database::getNada()->timestampFormatIso()
                );
            }
        }

        $db = Model_Database::getAdapter();

        $db->update(
            'accountinfo',
            $values,
            $db->quoteInto('hardware_id = ?', $this->_computer->getId())
        );
    }

    /**
     * Return the datatypes of all user defined fields
     *
     * Reimplementation that just proxies {@link getTypes()}.
     * @return array Associative array with the datatypes.
     */
    public function getPropertyTypes()
    {
        return Model_UserDefinedInfo::getTypes();
    }

    /**
     * Static variant of {@link getPropertyTypes()}
     * @return array Associative array with the datatypes
     */
    static function getTypes()
    {
        if (empty(self::$_allTypesStatic)) { // Query database only once
            $columns = Model_Database::getNada()->getTable('accountinfo')->getColumns();
            foreach ($columns as $column) {
                $name = $column->getName();
                if ($name == 'hardware_id') {
                    continue;
                }
                switch ($column->getDatatype()) {
                    case Nada::DATATYPE_VARCHAR:
                        $type = 'text';
                        break;
                    case Nada::DATATYPE_INTEGER:
                        $type = 'integer';
                        break;
                    case Nada::DATATYPE_FLOAT:
                        $type = 'float';
                        break;
                    case Nada::DATATYPE_DATE:
                        $type = 'date';
                        break;
                    case Nada::DATATYPE_CLOB:
                        $type = 'clob';
                        break;
                    case Nada::DATATYPE_BLOB:
                        // Ignore column, its values are always NULL.
                        // Attachments are handled in temp_files table.
                        $type = null;
                        break;
                    default:
                        throw new UnexpectedValueException(
                            'Invalid datatype: ' . $column->getDatatype()
                        );
                }
                if ($type) {
                    self::$_allTypesStatic[$name] = $type;
                }
            }
        }
        return self::$_allTypesStatic;
    }

    /**
     * Static variant of {@link getPropertyType()}
     * @param string $property Property whose datatype to retrieve
     * @return string Datatype (text, integer, float, date)
     */
    static function getType($property)
    {
        $types = self::getTypes();
        if (isset($types[$property])) {
            return $types[$property];
        } else {
            throw new UnexpectedValueException('Unknown property: ' . $property);
        }
    }

    /**
     * Return array of all defined fields
     * @return array
     **/
    static function getFields()
    {
        return array_keys(self::getTypes());
    }

    /**
     * Add a field
     * @param string $name Field name
     * @param string $type One of text, integer, float or date
     * @throws InvalidArgumentException if column exists or is a system column
     **/
    static function addField($name, $type)
    {
        if ($name == 'tag' or $name == 'hardware_id') {
            throw new InvalidArgumentException("Column cannot have reserved name '$name'.");
        }
        $types = self::getTypes();
        if (isset($types[$name])) {
            throw new InvalidArgumentException("Column '$name' already exists.");
        }

        switch ($type) {
            case 'text':
                $datatype = Nada::DATATYPE_VARCHAR;
                break;
            case 'integer':
                $datatype = Nada::DATATYPE_INTEGER;
                break;
            case 'float':
                $datatype = Nada::DATATYPE_FLOAT;
                break;
            case 'date':
                $datatype = Nada::DATATYPE_DATE;
                break;
            case 'clob':
                $datatype = Nada::DATATYPE_CLOB;
                break;
            default:
                throw new InvalidArgumentException('Invalid datatype: ' . $type);
        }

        $nada = Model_Database::getNada();

        // Since $name can be an arbitrary string, NADA must quote it
        // unconditionally.
        $quoteAlways = $nada->quoteAlways; // preserve setting
        $nada->quoteAlways = true;

        if ($type == 'text') {
            $column = $nada->createColumn($name, $datatype, 255);
        } else {
            $column = $nada->createColumn($name, $datatype);
        }
        $nada->getTable('accountinfo')->addColumnObject($column);
        self::$_allTypesStatic[$name] = $type;

        $nada->quoteAlways = $quoteAlways; // restore setting
    }

    /**
     * Delete a field definition and all its values
     * @param string $field Field name
     * @throws InvalidArgumentException if column does not exist or is a system column
     **/
    static function deleteField($field)
    {
        if ($field == 'tag' or $field == 'hardware_id') {
            throw new InvalidArgumentException("Cannot delete system column '$field'.");
        }
        $types = self::getTypes();
        if (!isset($types[$name])) {
            throw new InvalidArgumentException("Unknown column: $field");
        }

        $nada = Model_Database::getNada();

        // Since $field can be an arbitrary string, NADA must quote it
        // unconditionally.
        $quoteAlways = $nada->quoteAlways; // preserve setting
        $nada->quoteAlways = true;

        $nada->getTable('accountinfo')->dropColumn($field);
        unset(self::$_allTypesStatic[$field]);

        $nada->quoteAlways = $quoteAlways; // restore setting
    }

    /**
     * Rename field
     * @param string $oldName Existing field name
     * @param string $newName New field name
     * @throws InvalidArgumentException if column does not exist or is a system column or new name exists
     **/
    static function renameField($oldName, $newName)
    {
        if ($oldName == 'tag' or $oldName == 'hardware_id') {
            throw new InvalidArgumentException("System column '$oldName' cannot be renamed.");
        }
        if ($newName == 'tag' or $newName == 'hardware_id') {
            throw new InvalidArgumentException("Column cannot be renamed to reserved name '$newName'.");
        }
        $types = self::getTypes();
        if (!isset($types[$oldName])) {
            throw new InvalidArgumentException('Unknown column: ' . $oldName);
        }
        if (isset($types[$newName])) {
            throw new InvalidArgumentException("Column '$newName' already exists.");
        }

        $nada = Model_Database::getNada();

        // Since field names can be arbitrary strings, NADA must quote them
        // unconditionally.
        $quoteAlways = $nada->quoteAlways; // preserve setting
        $nada->quoteAlways = true;

        $nada->getTable('accountinfo')->getColumn($oldName)->setName($newName);
        self::$_allTypesStatic = array(); // force re-read on next usage

        $nada->quoteAlways = $quoteAlways; // restore setting
    }
}
