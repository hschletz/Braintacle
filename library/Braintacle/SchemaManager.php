<?php
/**
 * Interface for database schema management
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
 * @package Library
 */
/**
 * Includes
 */
Braintacle_MDB2::setErrorReporting();
require_once('MDB2/Schema.php');
/**
 * Interface for database schema management
 *
 * This is the legacy schema manager. It is superseded by Database\SchemaManager
 * which must be used instead.
 *
 * @deprecated Used only internally by Database\SchemaManager for functionality not ported yet. Do not use directly.
 * @package Library
 */
class Braintacle_SchemaManager
{
    /**
     * Latest version of the database schema
     */
    const SCHEMA_VERSION = 8;

    /**
     * Application config
     * @var \Model\Config
     */
    protected $_config;

    /**
     * MDB2_Schema object
     * @var MDB2_Schema
     */
    protected $_schema;

    /**
     * NADA object
     * @var Nada
     */
     protected $_nada;

    /**
     * Zend_Log object
     * @var Zend_Log
     */
    protected $_logger;

    /**
     * List of all tables in the database (array of Nada_Table objects)
     * @var array
     */
    protected $_allTables;

    /**
     * Names of tables managed by this class
     * @var string[]
     */
    protected $_managedTables = array();

    /**
     * Path to Braintacle's base directory
     * @var string
     */
    protected $_basepath;

    /**
     * Constructor
     * @param \Zend_Log $logger Logger object
     * @param \MDB2_Driver_Common $mdb2 Database connection (default: connect automatically)
     */
    function __construct(Zend_Log $logger, MDB2_Driver_Common $mdb2=null)
    {
        $this->_config = \Library\Application::getService('Model\Config');

        if (is_null($mdb2)) {
            $mdb2 =  Braintacle_MDB2::factory();
        }
        $this->_schema = MDB2_Schema::factory(
            $mdb2,
            array (
                'quote_identifier' => true,
                'force_defaults' => false,
                'portability' => MDB2_PORTABILITY_ALL ^ MDB2_PORTABILITY_EMPTY_TO_NULL,
            )
        );
        $this->_logger = $logger;
        $this->_nada = \Library\Application::getService('Database\Nada');
        $this->_allTables = $this->_nada->getTables();
        $this->_basepath = dirname(APPLICATION_PATH);
    }

    /**
     * Update database automatically
     *
     * This is the simplest way to update the database. It performs all
     * necessary steps to update the database. Alternatively, the other methods
     * can be called manually.
     */
    public function updateAll()
    {
        $this->_config->schemaVersion = self::SCHEMA_VERSION;
    }

    /**
     * Check for database update requirement
     *
     * This method evaluates the SchemaVersion option. If it is not present or
     * less than {@link SCHEMA_VERSION}, a database update is required.
     *
     * This method also checks for userdefined fields that need conversion.
     *
     * @return bool TRUE if update is required.
     */
    public function isUpdateRequired()
    {
        // Check for presence of 'config' table first
        if (isset($this->_allTables['config'])) {
            return ($this->_config->schemaVersion < self::SCHEMA_VERSION);
        } else {
            // Database is empty
            return true;
        }
    }

    /**
     * Check for compatibility with unmodified OCS Inventory NG
     *
     * Braintacle's database schema is not compatible with unmodified
     * installations of OCS Inventory NG. Braintacle works with the OCS schema
     * too, but a database update would destroy compatibility. This method
     * checks the current database for compatibility - compatible databases
     * should be managed via ocsreports instead unless the loss of compatibility
     * is OK.
     * @return bool TRUE if database is compatible
     */
    public function isOcsCompatible()
    {
        // Only MySQL databases can be compatible
        if (!$this->_nada->isMysql()) {
            return false;
        }
        // Check for presence of 'config' table first because the following code
        // would throw an exception otherwise. A database without this table is
        // considered not compatible.
        if (!isset($this->_allTables['config'])) {
            return false;
        }
        // The SchemaVersion option will be NULL for databases not managed by
        // Braintacle.
        if (is_null($this->_config->schemaVersion)) {
            return true;
        }
        // SchemaVersion present => database has previously been managed by
        // Braintacle.
        return false;
    }

}
