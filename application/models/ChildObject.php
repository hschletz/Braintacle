<?php
/**
 * Base class for all child objects belonging to a computer
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
 * Base class for all child objects belonging to a computer.
 * @package Models
 */
abstract class Model_ChildObject extends Model_Abstract
{
    /**
     * Name of corresponding XML element
     * @var string
     */
    protected $_xmlElementName;

    /**
     * Map of XML child elements
     *
     * All valid elements should be defined here. Values should be set to NULL
     * if no property is defined for a particular element.
     * The keys should be sorted lexically, just like the agents do, to simplify
     * comparision of generated files.
     * @var array
     */
    protected $_xmlElementMap = array();

    /** Return a statement|select object with all objects matching criteria.
     *
     * The default implementation provides a filter 'computer' which accepts
     * a computer's ID as search argument. To make it work, a derived class has
     * to set the $_tableName property.
     *
     * This method should not be bound to an instance, but cannot be made static
     * due to limitations of PHP before 5.3. The typical usage is to instantiate
     * a dummy object and call this method.
     * @param array $columns Logical properties to be returned. NULL to select all properties.
     * @param string $order Property to sort by. If NULL, the value of the $_preferredOrder property will be used.
     * @param string $direction One of [asc|desc]
     * @param array $filters Associative array of filters to apply.
     * Key: predefined filter string, base class provides 'Computer'.
     * Value: search parameter
     * If more than one filter is specified, all filters must match.
     * @param bool $query Perform query and return a Zend_Db_Statement object (default).
     *                    Set to false to return a Zend_Db_Select object.
     * @return Zend_Db_Statement|Zend_Db_Select Query result or Query
     */
    public function createStatement(
        $columns=null,
        $order=null,
        $direction='asc',
        $filters=null,
        $query=true
    )
    {
        $db = Zend_Registry::get('db');

        if (is_array($columns)) {
            foreach ($columns as $column) {
                $columnNames[] = $this->_propertyMap[$column];
            }
        } else {
            $columnNames = array_values($this->_propertyMap);
        }
        if (is_null($order)) {
            $order = $this->_preferredOrder;
        }
        $order = self::getOrder($order, $direction, $this->_propertyMap);

        $select = $db->select()
            ->from($this->_tableName, $columnNames)
            ->order($order);

        if (!is_null($filters) and array_key_exists('Computer', $filters)) {
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
     * To make this work, a derived class has to set the $_tableName property.
     * @return string Table name
     */
    public function getTableName()
    {
        return $this->_tableName;
    }

    /**
     * Generate a DOMElement object from current data
     *
     * @param Model_DomDocument_InventoryRequest $document DOMDocument from which to create elements.
     * @return DOMElement DOM object ready to be appended to the document.
     */
    public function toDomElement(Model_DomDocument_InventoryRequest $document)
    {
        // $_xmlElementName must be defined by a subclass
        if (empty($this->_xmlElementName)) {
            throw new Exception('No XML element defined for class ' . get_class($this));
        }

        // Create base element
        $element = $document->createElement($this->_xmlElementName);

        // Create child elements, 1 per property
        foreach ($this->_xmlElementMap as $name => $property) {
            if (!$property) {
                continue; // Don't generate elements for undefined properties
            }
            $value = $this->getProperty($property, true); // Export raw value
            if (!empty($value)) { // Don't generate empty elements
                $element->appendChild($document->createElementWithContent($name, $value));
            }
        }

        return $element;
    }

    /**
     * Get the name of the corresponding XML element
     * @return string XML element name or NULL if no element is defined
     */
    public function getXmlElementName()
    {
        return $this->_xmlElementName;
    }

}
