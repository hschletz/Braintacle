<?php
/**
 * Class providing access to user defined fields
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
 Includes
 */
require_once 'Braintacle/MDB2.php';
/**
 * User defined fields for a computer
 *
 * The 'tag' field is always present. Other fields may be defined by the
 * administrator. Their names are always returned lowercase.
 * @package Models
 */
class Model_UserDefinedInfo
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
     * Return the values of all user defined fields for a given computer
     * @param integer $id ID of computer for which the user defined fields should be retrieved.
     * @return array Associative array with the values. The field names are used as keys.
     */
    static function getValues($id)
    {
        $db = Zend_Registry::get('db');

        $result = $db->fetchRow(
            'SELECT * FROM accountinfo WHERE hardware_id = ?',
            (int) $id,
            Zend_Db::FETCH_ASSOC
        );
        unset($result['hardware_id']);
        return $result;
    }

    /**
     * Set the values of all user defined fields for a given computer
     * @param integer $id ID of computer for which the user defined fields should be retrieved.
     * @param array $values Associative array with the values.
     */
    static function setValues($id, $values)
    {
        $db = Zend_Registry::get('db');
        $db->update('accountinfo', $values, $db->quoteInto('hardware_id = ?', (int) $id));
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
     *
     * This method makes an extra database connection via MDB2. The result is
     * stored statically, so that no extra connections are made when this
     * gets called more than once.
     * @return array Associative array with the datatypes
     */
    static function getTypes()
    {
        if (empty(Model_UserDefinedInfo::$_allTypesStatic)) { // Query database only once
            Braintacle_MDB2::setErrorReporting();
            $mdb2 = Braintacle_MDB2::factory();
            $mdb2->loadModule('Reverse');
            $columns = $mdb2->reverse->tableInfo('accountinfo');
            Braintacle_MDB2::resetErrorReporting();

            foreach ($columns as $column) {
                $name = $column['name'];
                if ($name != 'hardware_id') {
                    Model_UserDefinedInfo::$_allTypesStatic[$name] = $column['mdb2type'];
                }
            }
        }
        return Model_UserDefinedInfo::$_allTypesStatic;
    }

}
