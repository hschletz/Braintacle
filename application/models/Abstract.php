<?php
/**
 * Base class for most Braintacle model classes
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
 * Base class for most Braintacle model classes
 *
 * Model_Abstract defines generic accessors via {@link __call()}, the magic
 * methods {@link __get()} and {@link __set()} and the higher level methods
 * {@link getProperty()} and {@link setProperty()} which use the
 * {@link $_propertyMap} property to map logical identifiers to actual
 * schema identifiers, and some utility methods to deal with logical identifiers.
 *
 * This is required because Zend coding standards require camelCasing,
 * which is not portable across different DBMS (lowercase folding is used
 * for portability reasons). Some database identifiers may contain underscores,
 * which are not allowed by Zend standards either. Finally, some identifiers in
 * the database schema are not very meaningful. The property map, along with
 * customized accessors, allows redefining the interface which will be used
 * throughout the application, without having to deal with schema details.
 *
 * This class also implements PHP's Iterator interface, allowing instances to
 * be iterated in a foreach() loop. The logical identifiers will be returned as
 * keys, and the values are processed by {@link getProperty()}.
 *
 * Trying to use properties that are not defined in {@link $propertyMap} will
 * cause an exception to be thrown.
 *
 * To use this class with query results, pass the name of a derived class to {@link 
 * http://framework.zend.com/manual/en/zend.db.statement.html#zend.db.statement.fetching.fetchobject
 * Zend_Db_Statement::fetchObject()}. Then use getLogicalIdentifier() and
 * setLogicalIdentifier() to access the properties.
 *
 * {@link getProperty()} and {@link setProperty()} can also be called directly.
 * This is useful when a property name is determined at runtime. For example,
 * to set property values from an array, you can do this:
 *
 * <code>
 * foreach ($data as $property => $value) {
 *     $this->setProperty($property, $value);
 * }
 * </code>
 *
 * Derived classes may override {@link getProperty()} and {@link setProperty()}
 * to process some values (for example, to lowercase values or map enum-like
 * values to a nicer format). This will also affect the automatic get...() and
 * set...() methods. This is preferred over defining a specific accessor method
 * which might not work under all circumstances. The {@link getProperty()}
 * implementation should respect the $rawValue parameter. If this is set to
 * TRUE, the unprocessed value should be returned. Example:
 *
 * <code>
 * class Model_Derived extends Model_Abstract
 * {
 *     public function getProperty($property, $rawValue=false)
 *     {
 *         // return LogicalName property lowercased unless raw value is requested
 *         if (!$rawValue && $property == 'LogicalName') {
 *             return strtolower(parent::getProperty($property));
 *         }
 *         // All other properties are returned unchanged
 *         return parent::getProperty($property);
 *     }
 * }
 * </code>
 *
 * Overriding {@link setProperty()} is similar, with the value given in the second
 * parameter.
 *
 * Although the query-generated properties are public, they should not be used.
 * Always define a property map and use the accessor methods.
 *
 * Due to limitations of PHP before 5.3, the property map cannot be made static.
 * If you need it outside of an object instance (i.e. in a static method or
 * outside of the class), you can instantiate a dummy object and call its
 * {@link getPropertyMap} method.
 * @package Models
 */
abstract class Model_Abstract implements Iterator
{

    /**
     * The property map
     *
     * Override this in a derived class.
     * @var array
     */
    protected $_propertyMap = array();

    /**
     * Datatypes of non-text properties.
     *
     * Override this in a derived class if necessary.
     * @see getPropertyType
     * @var array
     */
    protected $_types = array();

    /**
     * Datatypes of all properties.
     *
     * Initially empty, typically managed by {@link getPropertyTypes()}.
     * Do not use it directly - always call getPropertyTypes().
     * @var array
     */
    protected $_allTypes = array();

    /**
     * Raw values, as retrieved from the database
     *
     * @var array
     */
    private $_data = array();

    /**
     * Internal state of iterator
     *
     * @var bool
     */
    private $_iteratorValid;

    /**
     * Constructor
     */
    function __construct()
    {
        // Constructor is empty. It exists only to allow derived classes to call
        // parent::__construct() from their own constructor, regardless of the
        // parent's implementation which a derived class does not have to know
        // about. See https://bugs.php.net/bug.php?id=55864
    }

    /**
     * Generic accessor method
     *
     * It provides the getLogicalIdentifier() and setLogicalIdentifier()
     * methods to access the 'LogicalIdentifier' property.
     * @param string $name Name of called method
     * @param array $arguments Arguments passed to method.
     * @return mixed For get...(), the property value.
     */
    function __call($name, $arguments)
    {
        $type = substr($name, 0, 3);
        $property = substr($name, 3);

        switch ($type) {
        case 'get':
            return $this->getProperty($property);
            break;
        case 'set':
            $this->setProperty($property, $arguments[0]);
            break;
        default:
            throw new BadMethodCallException(
                'Call to undefined method: ' . $name . '()'
            );
        }
    }

    /**
     * Magic method to retrieve a property directly
     *
     * @param string $property Raw property name
     * @return mixed Raw property value
     */
    function __get($property)
    {
        if (in_array($property, $this->_propertyMap)) {
            if (array_key_exists($property, $this->_data)) {
                return $this->_data[$property];
            } else {
                throw new RuntimeException('Tried to access uninitialized property ' . $property);
            }
        } else {
            throw new UnexpectedValueException(
                'Unknown property: ' . $property
            );
        }
    }

    /**
     * Magic method to set a property directly
     *
     * @param string $property Raw property name
     * @param mixed $value Raw property value
     */
    function __set($property, $value)
    {
        if (in_array($property, $this->_propertyMap)) {
            $this->_data[$property] = $value;
        } else {
            throw new UnexpectedValueException(
                'Unknown property: ' . $property
            );
        }
    }

    /**
     * Retrieve a property by its logical name
     *
     * Properties of type 'timestamp' and 'date' are automatically converted to
     * a Zend_Date object unless $rawValue is true or the value is NULL.
     * A derived class may process any value.
     *
     * @param string $property Logical property name
     * @param bool $rawValue If TRUE, do not process the value. Default: FALSE
     * @return mixed Property value. Derived class may have processed the value.
     */
    public function getProperty($property, $rawValue=false)
    {
        $columnName = $this->getColumnName($property);
        $value = $this->__get($columnName);
        $type = $this->getPropertyType($property);

        if (!$rawValue and !is_null($value) and ($type == 'timestamp' or $type == 'date')) {
            $value = new Zend_Date($value, Zend_Date::ISO_8601);
        }

        return $value;
    }

    /**
     * Retrieve all properties as an array
     *
     * This calls {@link getProperty()} internally, so the same rules for data
     * mangling apply and there is typically no need for overriding this method.
     * @param bool $rawValue If TRUE, do not process the values. Default: FALSE
     * @return array Associative array with property names as key
     */
    public function getProperties($rawValues=false)
    {
        $result = array();
        foreach ($this->_propertyMap as $property => $column) {
            $result[$property] = $this->getProperty($property, $rawValues);
        }
        return $result;
    }

    /**
     * Set a property by its logical name.
     *
     * If the given value is a Zend_Date object, it will be converted to a
     * string in ISO 8601 notation. A derived class may process any value.
     *
     * @param string $property Logical property name
     * @param mixed $value Property value.
     */
    public function setProperty($property, $value)
    {
        if ($value instanceof Zend_Date) {
            $value = $value->get(Zend_Date::ISO_8601);
        }
        $columnName = $this->getColumnName($property);
        $this->__set($columnName, $value);
    }

    /**
     * Return the property map.
     * @return array The property map.
     */
    public function getPropertyMap()
    {
        return $this->_propertyMap;
    }

    /**
     * Return the datatype of a property
     *
     * The default implementation retrieves the datatype from the result of
     * {@link getPropertyTypes()}.
     * @param string $property Property name
     * @return string One of (text|boolean|integer|decimal|float|date|time|timestamp|blob|clob|enum)
     */
    public function getPropertyType($property)
    {
        $types = $this->getPropertyTypes();
        if (isset($types[$property])) {
            return $types[$property];
        } else {
            throw new UnexpectedValueException('Unknown property: ' . $property);
        }
    }

    /**
     * Return the datatypes of all properties
     *
     * The default implementation returns 'text' for each property unless
     * overridden in {@link $_types}. The datatypes are abstract types as used
     * by MDB2.
     * Additionally, the type 'enum' is available. It does not map to a
     * particular datatype, and the database may store it as a different type
     * (integer or string), even if the database supports the enum datatype.
     * It's up to the application how to handle it - typically as a symbolic
     * string.
     * @link http://pear.php.net/manual/en/package.database.mdb2.datatypes.php
     * @return array Associative array with property as key and type as value.
     */
    public function getPropertyTypes()
    {
        if (empty($this->_allTypes)) { // build _allTypes only once
            foreach (array_keys($this->_propertyMap) as $property) {
                if (isset($this->_types[$property])) {
                    $this->_allTypes[$property] = $this->_types[$property];
                } else {
                    $this->_allTypes[$property] = 'text';
                }
            }
        }
        return $this->_allTypes;
    }

    /**
     * Get the real column name for a property
     * @param string $property Logical property name
     * @return string Column name to be used in SQL queries
     */
    public function getColumnName($property)
    {
        if (isset($this->_propertyMap[$property])) {
            return $this->_propertyMap[$property];
        } else {
            throw new UnexpectedValueException('Unknown property: ' . $property);
        }
    }

    /**
     * Compose ORDER BY clause from logical identifier
     *
     * $property is the logical property name. The special value 'id' sorts by
     * the 'id' column, even when it is not a regular property.
     * @param string $order Property to sort by.
     * @param string $direction One of [asc|desc]
     * @param array $propertyMap Property map to use. Must be passed explicitly because this method is static.
     * @return string ORDER BY clause with schema identifier, NULL if $order was empty
     */
    static function getOrder($order, $direction, $propertyMap)
    {
        if (empty($order)) {
            return NULL;
        }

        if (isset($propertyMap[$order])) {
            $order = $propertyMap[$order];
        } elseif ($order != 'id') {
            throw new UnexpectedValueException('Unknown property: ' . $order);
        }

        if ($direction) {
            $order .= ' ' . $direction;
        }
        return $order;
    }

    /**
     * Part of iterator implementation. Do not call directly.
     */
    public function current()
    {
        return $this->getProperty(key($this->_propertyMap));
    }

    /**
     * Part of iterator implementation. Do not call directly.
     */
    public function key()
    {
        return key($this->_propertyMap);
    }

    /**
     * Part of iterator implementation. Do not call directly.
     */
    public function next()
    {
        if (next($this->_propertyMap) === false) {
            $this->_iteratorValid = false;
        }
    }

    /**
     * Part of iterator implementation. Do not call directly.
     */
    public function rewind()
    {
        reset($this->_propertyMap);
        $this->_iteratorValid = true;
    }

    /**
     * Part of iterator implementation. Do not call directly.
     */
    public function valid()
    {
        return $this->_iteratorValid;
    }

}
