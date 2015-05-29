<?php
/**
 * Model class to provide some information about the database
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
 * Model class to provide some information about the database
 * @package Models
 */
class Model_Database
{
    /**
     * Cache for table names
     *
     * This is managed by {@link _listTables()}. Do not use directly.
     * @var array
     */
    protected static $_allTables;

     /**
     * Get a list with all table names
     * @return array
     */
    protected static function _listTables()
    {
        if (!is_array(self::$_allTables)) {
            $db = self::getAdapter();
            self::$_allTables = $db->listTables();
        }
        return self::$_allTables;
    }

    /**
     * Retrieve global adapter object
     * @return Zend_Db_Adapter_Abstract
     **/
    public static function getAdapter()
    {
        return Zend_Registry::get('db');
    }

    /**
     * Get NADA object set up for application's database
     * @return Nada_Database
     */
    public static function getNada()
    {
        return \Library\Application::getService('Database\Nada');
    }

}
