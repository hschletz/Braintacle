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
 * The 'TAG' field is always present. Other fields may be defined by the
 * administrator.
 * @package Models
 */
class Model_UserDefinedInfo extends Model_Abstract
{

    /**
     * Internal identifier for text, integer and float columns
     **/
    const INTERNALTYPE_TEXT = 0;

    /**
     * Internal identifier for clob columns
     **/
    const INTERNALTYPE_TEXTAREA = 1;

    /**
     * Internal identifier for blob columns
     **/
    const INTERNALTYPE_BLOB = 5;

    /**
     * Internal identifier for date columns
     **/
    const INTERNALTYPE_DATE = 6;

    /**
     * Datatypes of all properties.
     *
     * Initially empty, typically managed by {@link getTypes()}.
     * Do not use it directly - always call getTypes() or getPropertyTypes().
     * @var array
     */
    static protected $_allTypesStatic = array();

    /**
     * Map of field names => column names
     *
     * This is the static equivalent to the property map. It gets populated by
     * getTypes().
     * @var array
     */
    static protected $_columnNames = array();

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

        // Set up property map from $_columnNames which got populated by getTypes().
        $this->_propertyMap = self::$_columnNames;

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
            $statement = Model_Database::getAdapter()->query(
                "SELECT id, type, name FROM accountinfo_config WHERE account_type = 'COMPUTERS'"
            );
            // Iterate over result set and determine name and type of each
            // field. Unsupported field types will be silently ignored.
            while ($field = $statement->fetchObject()) {
                $name = $field->name;
                if ($name == 'TAG') {
                    $columnName = 'tag';
                    $type = 'text';
                } else {
                    $columnName = 'fields_' . $field->id;
                    $column = $columns[$columnName];
                    switch ($field->type) {
                        case self::INTERNALTYPE_TEXT:
                            // Can be text, integer or float. Evaluate column
                            // datatype.
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
                                default:
                                    throw new UnexpectedValueException(
                                        'Invalid datatype: ' . $column->getDatatype()
                                    );
                            }
                            break;
                        case self::INTERNALTYPE_TEXTAREA:
                            $type = 'clob';
                            break;
                        case self::INTERNALTYPE_DATE:
                            // ocsreports creates date columns as varchar(10)
                            // and stores values in a non-ISO format. Silently
                            // ignore these fields. Only accept real date
                            // columns.
                            if ($column->getDatatype() == Nada::DATATYPE_DATE) {
                                $type = 'date';
                            } else {
                                $type = '';
                            }
                            break;
                        default:
                            // Silently ignore unsupported field types.
                            $type = '';
                    }
                }
                if ($type) {
                    self::$_allTypesStatic[$name] = $type;
                    self::$_columnNames[$name] = $columnName;
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
     * @param string $type One of text, clob, integer, float or date
     * @throws InvalidArgumentException if column exists or is a system column
     **/
    static function addField($name, $type)
    {
        $types = self::getTypes();
        if (isset($types[$name])) {
            throw new InvalidArgumentException("Column '$name' already exists.");
        }

        switch ($type) {
            case 'text':
                $datatype = Nada::DATATYPE_VARCHAR;
                $internalType = self::INTERNALTYPE_TEXT;
                break;
            case 'integer':
                $datatype = Nada::DATATYPE_INTEGER;
                $internalType = self::INTERNALTYPE_TEXT;
                break;
            case 'float':
                $datatype = Nada::DATATYPE_FLOAT;
                $internalType = self::INTERNALTYPE_TEXT;
                break;
            case 'date':
                $datatype = Nada::DATATYPE_DATE;
                $internalType = self::INTERNALTYPE_DATE;
                break;
            case 'clob':
                $datatype = Nada::DATATYPE_CLOB;
                $internalType = self::INTERNALTYPE_TEXTAREA;
                break;
            default:
                throw new InvalidArgumentException('Invalid datatype: ' . $type);
        }

        $db = Model_Database::getAdapter();
        $nada = Model_Database::getNada();

        $db->beginTransaction();
        $order = $db->fetchOne(
            "SELECT MAX(show_order) + 1 FROM accountinfo_config WHERE account_type = 'COMPUTERS'"
        );
        $db->insert(
            'accountinfo_config',
            array(
                'type' => $internalType,
                'name' => $name,
                'id_tab' => 1,
                'show_order' => $order,
                'account_type' => 'COMPUTERS'
            )
        );
        $columnName = 'fields_' . $db->lastInsertId('accountinfo_config', 'id');
        if ($type == 'text') {
            $column = $nada->createColumn($columnName, $datatype, 255);
        } else {
            $column = $nada->createColumn($columnName, $datatype);
        }
        $nada->getTable('accountinfo')->addColumnObject($column);
        $db->commit();

        self::$_allTypesStatic[$name] = $type;
    }

    /**
     * Delete a field definition and all its values
     * @param string $field Field name
     * @throws InvalidArgumentException if column does not exist or is a system column
     **/
    static function deleteField($field)
    {
        if ($field == 'TAG') {
            throw new InvalidArgumentException("Cannot delete system column 'TAG'.");
        }
        $types = self::getTypes();
        if (!isset($types[$field])) {
            throw new InvalidArgumentException("Unknown column: $field");
        }

        $db = Model_Database::getAdapter();
        $db->beginTransaction();
        $id = $db->fetchOne(
            "SELECT id FROM accountinfo_config WHERE name = ? AND account_type = 'COMPUTERS'",
            $field
        );
        $db->delete('accountinfo_config', array('id = ?' => $id));
        Model_Database::getNada()->getTable('accountinfo')->dropColumn('fields_' . $id);
        $db->commit();

        unset(self::$_allTypesStatic[$field]);
    }

    /**
     * Rename field
     * @param string $oldName Existing field name
     * @param string $newName New field name
     * @throws InvalidArgumentException if column does not exist or is a system column or new name exists
     **/
    static function renameField($oldName, $newName)
    {
        if ($oldName == 'TAG') {
            throw new InvalidArgumentException("System column 'TAG' cannot be renamed.");
        }
        if ($newName == 'TAG') {
            throw new InvalidArgumentException("Column cannot be renamed to reserved name 'TAG'.");
        }
        $types = self::getTypes();
        if (!isset($types[$oldName])) {
            throw new InvalidArgumentException('Unknown column: ' . $oldName);
        }
        if (isset($types[$newName])) {
            throw new InvalidArgumentException("Column '$newName' already exists.");
        }

        $db = Model_Database::getAdapter();
        $db->update(
            'accountinfo_config',
            array('name' => $newName),
            array(
                'name = ?' => $oldName,
                'account_type = ?' => 'COMPUTERS'
            )
        );

        self::$_allTypesStatic = array(); // force re-read on next usage
    }
}
