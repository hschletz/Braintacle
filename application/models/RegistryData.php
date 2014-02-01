<?php
/**
 * Class representing inventoried registry data
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
 * Inventoried registry data
 *
 * Properties:
 *
 * - **Value** Model_RegistryValue object
 * - **Data** Registry Data
 *
 * Don't confuse 'Value' with its content. In registry terms, 'Value' refers to
 * the name of the entry that holds the data, while 'Key' is the container that
 * holds the value. While this terminology differs from common usage, it is used
 * throughout the official Windows documentation and API, and Braintacle follows
 * this convention.
 * @package Models
 */
class Model_RegistryData extends Model_ChildObject
{
    /** {@inheritdoc} */
    protected $_propertyMap = array(
        'Data' => 'data', // from complex SQL expression
    );

    /** {@inheritdoc} */
    protected $_tableName = 'registry';

    /** {@inheritdoc} */
    protected $_preferredOrder = 'Value.Name';

    /**
     * Value definition
     * @var Model_RegistryValue
     **/
    protected $_value;

    /** {@inheritdoc} */
    function __construct()
    {
        parent::__construct();
        // When instantiated from fetchObject(), __set() gets called before the
        // constructor is invoked, which may initialize the property. Don't
        // overwrite it in that case.
        if (!$this->_value) {
            $this->_value = new Model_RegistryValue;
        }
    }

    /** {@inheritdoc} */
    public function createStatement(
        $columns=null,
        $order=null,
        $direction='asc',
        $filters=null,
        $query=true
    )
    {
        // The 'data' column is not valid at this point. Filter it out here and
        // add it later if requested.
        if (is_array($columns)) {
            $index = array_search('Data', $columns);
            if ($index !== false) {
                unset($columns[$index]);
                $addDataColumn = true;
            } else {
                $addDataColumn = false;
            }
        } else {
            // Pass empty array instead of NULL tp prevent parent implementation
            // from adding the 'data' column.
            $columns = array();
            $addDataColumn = true;
        }

        // If the result is ordered by Value.*, generating the ORDER BY clause
        // would fail. Add a valid dummy ordering which gets overridden later.
        if ($order === null or preg_match('/^Value\.(.*)/', $order, $matches)) {
            $order = 'Data';
            $orderByValue = $matches[1];
        }

        // Call parent implementation without querying
        $select = parent::createStatement(
            $columns,
            $order,
            $direction,
            $filters,
            false
        );

        // Generate expressions for Value.ValueInventoried property
        // ('value_inventoried' column) and 'Data' property ('data' column). If
        // regconfig.regvalue is '*', registry.regvalue is
        // 'value_inventoried=data', so that the column contents must be
        // extracted from this string. In any other case, the contents map
        // directly to regconfig.regvalue and registry.regvalue.
        if ($addDataColumn) {
            $select->columns(
                array(
                    'data' => new Zend_Db_Expr(
                        <<<EOT
                        CASE
                            WHEN regconfig.regvalue = '*'
                                THEN SUBSTRING(registry.regvalue FROM POSITION('=' IN registry.regvalue) + 1)
                            ELSE registry.regvalue
                        END
EOT
                    )
                )
            );
        }
        $select->join(
            'regconfig',
            'regconfig.name = registry.name',
            array(
                'id',
                'name',
                'regtree',
                'regkey',
                'regvalue',
                'value_inventoried' => new Zend_Db_Expr(
                    <<<EOT
                    CASE
                        WHEN regconfig.regvalue='*'
                            THEN SUBSTRING(registry.regvalue FROM 1 FOR POSITION('=' in registry.regvalue) - 1)
                        ELSE regconfig.regvalue
                    END
EOT
                )
            )
        );

        if (isset($orderByValue)) {
            // Replace fake ordering with ordering from Model_RegistryValue.
            $select->reset('order');
            if ($orderByValue == 'ValueInventoried') {
                $select->order("value_inventoried $direction");
            } else {
                $dummy = new Model_RegistryValue;
                $select->order(
                    Model_RegistryValue::getOrder(
                        $orderByValue,
                        $direction,
                        $dummy->getPropertyMap()
                    )
                );
            }
        }

        if ($query) {
            return $select->query();
        } else {
            return $select;
        }
    }

    /** {@inheritdoc} */
    function __set($property, $value)
    {
        // When instantiated from fetchObject(), this gets called before
        // __construct(). Initialize property if necessary.
        if (!$this->_value) {
            $this->_value = new Model_RegistryValue;
        }
        switch ($property) {
            case 'data':
                parent::__set($property, $value);
                break;
            case 'value_inventoried':
                $this->_value->setValueInventoried($value);
                break;
            default:
                // Unknown columns are passed to Model_RegistryValue.
                $this->_value->$property = $value;
        }
    }

    /** {@inheritdoc} */
    public function getProperty($property, $rawValue=false)
    {
        if ($property == 'Value') {
            if ($rawValue) {
                return $this->_value->getName();
            } else {
                return $this->_value;
            }
        } elseif ($property == 'Data' and $rawValue and $this->_value->getValueConfigured() === null) {
            // Reassemble the compound data ('value=data')
            return $this->_value->getValueInventoried() . '=' . parent::getProperty('Data');
        } else {
            return parent::getProperty($property, $rawValue);
        }
    }

    /** {@inheritdoc} */
    public function setProperty($property, $value)
    {
        if ($property == 'Value') {
            $this->_value = $value;
        } else {
            parent::setProperty($property, $value);
        }
    }

    /** {@inheritdoc} */
    public function getPropertyType($property)
    {
        if (preg_match('/^Value\.(.*)/', $property, $matches)) {
            $type = $this->_value->getPropertyType($matches[1]);
        } elseif ($property == 'Value') {
            $type = get_class($this->_value);
        } else {
            $type = parent::getPropertyType($property);
        }
        return $type;
    }
}
