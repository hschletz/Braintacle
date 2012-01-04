<?php
/**
 * Model class to provide some information about the database
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
 * @filesource
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
     * Global NADA object
     *
     * This is managed by {@link getNada()}. Do not use directly.
     * @var Nada_Dbms
     */
   protected static $_nada;

     /**
     * Get a list with all table names
     * @return array
     */
    protected static function _listTables()
    {
        if (!is_array(self::$_allTables)) {
            $db = Zend_Registry::get('db');
            self::$_allTables = $db->listTables();
        }
        return self::$_allTables;
    }

    /**
     * Return status of asset tag blacklisting support
     *
     * Blacklisting asset tags for duplicates search (in addition to serials and
     * MAC addresses) is a Braintacle-specific extension that requires the
     * presence of a table which is not part of an original OCS Inventory
     * database. To support these databases, the presence of the table should be
     * queried via this method and the blacklist should only be applied if
     * available.
     * @return bool TRUE if table exists
     */
    public static function supportsAssetTagBlacklist()
    {
        return in_array('braintacle_blacklist_assettags', self::_listTables());
    }

    /**
     * Get NADA object set up for application's database
     * @return Nada_Dbms
     */
    public static function getNada()
    {
        if (!self::$_nada) {
            require_once('NADA/Nada.php');
            self::$_nada = Nada::factory(Zend_Registry::get('db'));
        }
        return self::$_nada;
    }

}
