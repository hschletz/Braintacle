<?php
/**
 * Base class for most Braintacle model classes
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
 * Base class for most Braintacle model classes
 *
 * Model_Abstract defines generic accessors via {@link __call()}, the magic
 * methods {@link __get()} and {@link __set()} and the higher level methods
 * {@link getProperty()} and {@link setProperty()} which use the
 * {@link $_propertyMap} property to map logical identifiers to actual
 * schema identifiers, and some utility methods to deal with logical identifiers.
 * The \ArrayAccess interface is the preferred way of property access.
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
 * keys, and the values are processed by {@link getProperty()}. Only properties
 * that were previously set are iterated.
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
 *     foreach ($data as $property => $value) {
 *         $this->setProperty($property, $value);
 *     }
 *
 * Derived classes may override {@link getProperty()} and {@link setProperty()}
 * to process some values (for example, to lowercase values or map enum-like
 * values to a nicer format). This will also affect the automatic get...() and
 * set...() methods. This is preferred over defining a specific accessor method
 * which might not work under all circumstances. The {@link getProperty()}
 * implementation should respect the $rawValue parameter. If this is set to
 * TRUE, the unprocessed value should be returned. Example:
 *
 *     class Model_Derived extends Model_Abstract
 *     {
 *         public function getProperty($property, $rawValue=false)
 *         {
 *             // return LogicalName property lowercased unless raw value is requested
 *             if (!$rawValue and $property == 'LogicalName') {
 *                 return strtolower(parent::getProperty($property));
 *             }
 *             // All other properties are returned unchanged
 *             return parent::getProperty($property);
 *         }
 *     }
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
abstract class Model_Abstract extends \Model\ClientOrGroup
{
    /**
     * Application config
     * @var \Model\Config
     * @deprecated get config from service locator
     */
    protected $_config;

    /**
     * Constructor
     **/
    public function __construct($input=array(), $flags=0, $iteratorClass='ArrayIterator')
    {
        parent::__construct($input, $flags, $iteratorClass);
        $this->serviceLocator = \Library\Application::getService('ServiceManager');
        $this->_config = $this->serviceLocator->get('Model\Config');
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
            return $this[$property];
            break;
        case 'set':
            $this->offsetSet($property, $arguments[0]);
            break;
        default:
            throw new BadMethodCallException(
                'Call to undefined method: ' . $name . '()'
            );
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
}
