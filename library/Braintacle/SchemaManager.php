<?php
/**
 * Interface for database schema management
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
     * Database adapter
     * @var Zend_Db_Adapter_Abstract
     */
    protected $_db;

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
     * Array of userdefined columns to convert
     *
     * This is managed by getUserdefinedInfoToConvert(). Do not use directly.
     * @var mixed
     **/
    private $_userDefinedInfoToConvert = null;

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
        $this->_db = Model_Database::getAdapter();
        $this->_nada = Model_Database::getNada();
        if ($this->_nada->isMysql()) {
            $this->_nada->emulatedDatatypes = array(Nada::DATATYPE_TIMESTAMP);
        }
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
        $previousSchema = $this->getSchemaFromDatabase();
        $newSchema = $this->getSchemaFromXml($previousSchema);
        $this->updateSchema($previousSchema, $newSchema);
        $this->convertUserdefinedInfo();
        $this->_config->schemaVersion = self::SCHEMA_VERSION;
    }

    /**
     * Fix bad primary keys
     *
     * The original OCS Inventory schema (i.e. not managed by braintacle) has
     * some weird primary keys which MDB2_Schema would refuse to operate on.
     * These keys need to be dropped and re-created correctly before the schema
     * can be validated by MDB2_Schema. See schema/README.html for details.
     */
    public function fixKeys()
    {
        // Since OCS Inventory only supports MySQL, databases for any other DBMS
        // will essentially be managed exclusively by Braintacle so that this
        // operation will not be necessary.
        if (!$this->_nada->isMysql()) {
            return;
        }
        $this->_logger->info('Fixing keys...');
        $mdb2 = $this->_schema->db;

        // The definitions for some tables require an extra constraint to be
        // created before the bad PK can be dropped.
        $createConstraint = array(
            'journallog',
            'officepack',
            'snmp_cards',
            'snmp_cartridges',
            'snmp_cpus',
            'snmp_drives',
            'snmp_fans',
            'snmp_inputs',
            'snmp_localprinters',
            'snmp_memories',
            'snmp_modems',
            'snmp_networks',
            'snmp_ports',
            'snmp_powersupplies',
            'snmp_softwares',
            'snmp_sounds',
            'snmp_storages',
            'snmp_switchs',
            'snmp_trays',
            'snmp_videos',
            'virtualmachines',
        );
        // For the tables listed above plus some additional tables, the bad PK
        // needs to be dropped and recreated the right way.
        $fixTables = array(
            'controllers',
            'drives',
            'inputs',
            'memories',
            'modems',
            'monitors',
            'officepack',
            'ports',
            'printers',
            'registry',
            'slots',
            'softwares',
            'sounds',
            'storages',
            'videos',
        );
        $fixTables = array_merge($fixTables, $createConstraint);
        // Only existing tables can be processed.
        $fixTables = array_intersect($fixTables, array_keys($this->_allTables));

        // Templates for constraint creation
        $templatePrimary = array(
            'primary' => true,
            'fields' => array(
                'id' => array()
            )
        );
        $templateUnique = array(
            'unique' => true,
            'fields' => array(
                'id' => array()
            )
        );

        foreach ($fixTables as $table) {
            $pk = $mdb2->reverse->getTableConstraintDefinition($table, 'primary');
            $fields = $pk['fields'];
            // Check for bad PK
            if (count($fields) > 1 or !isset($fields['id'])) {
                $this->_logger->info('Fixing table ' . $table);
                if (in_array($table, $createConstraint)) {
                    $mdb2->manager->createConstraint($table, 'primary', $templateUnique);
                }
                $mdb2->manager->dropConstraint($table, 'primary', true);
                $mdb2->manager->createConstraint($table, 'primary', $templatePrimary);
            }
        }
        $this->_logger->info('done.');
    }

    /**
     * Retrieve schema definition from existing database
     * @return array MDB2_Schema-style definition
     */
    public function getSchemaFromDatabase()
    {
        // Fix bad PK first to make getDefinitionFromDatabase() work
        $this->fixKeys();

        $this->_logger->info('Retrieving existing definition from database...');

        $previousSchema = $this->_schema->getDefinitionFromDatabase();
        if (PEAR::isError($previousSchema)) {
            throw new RuntimeException($previousSchema->getUserInfo());
        }

        $this->_logger->info('done.');
        return $previousSchema;
    }

    /**
     * Retrieve latest schema definition from XML files
     *
     * The XML fragments in the schema/ directory are parsed and some
     * dynamically created fields from the current database are merged.
     * @param array $previousSchema Current database definition
     * @return array MDB2_Schema-style definition
     */
    public function getSchemaFromXml($previousSchema)
    {
        $this->_logger->info('Parsing XML definition...');

        // The schema is split into multiple files which MDB2_Schema cannot
        // handle. They need to be concatenated into a single temporary file.
        $temp = tmpfile();
        if (!$temp) {
            throw new RuntimeException('Could not create temporary file');
        }

        // Write header to temp file
        $name = $this->_nada->getName();
        $header = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>\n" .
                  "<database>\n" .
                  "<name>$name</name>\n" .
                  "<create>true</create>\n" .
                  "<overwrite>false</overwrite>\n";
        if (!fwrite($temp, $header)) {
            throw new RuntimeException('Error writing to temporary file');
        }

        // Iterate through schema directory and append every XML file.
        $dir = opendir("$this->_basepath/schema");
        if (!$dir) {
            throw new RuntimeException('Can\'t get a handle for schema directory');
        }
        while (($file = readdir($dir)) !== false ) {
            if (substr($file, -4) == '.xml') {
                $this->_managedTables[] = str_replace(array('ocs_', 'plugin_', '.xml'), '', $file);
                $content = file_get_contents("$this->_basepath/schema/$file");
                if ($content == false) {
                    throw new RuntimeException("Error reading $file");
                }
                if (!fwrite($temp, $content)) {
                    throw new RuntimeException('Error writing to temporary file');
                }
            }
        }

        // Write footer to temp file.
        if (!fwrite($temp, "</database>\n")) {
            throw new RuntimeException('Error writing to temporary file');
        }

        // Get temp file name and parse it.
        $meta = stream_get_meta_data($temp);
        $newSchema = $this->_schema->parseDatabaseDefinitionFile($meta['uri']);
        fclose($temp);
        if (PEAR::isError($newSchema)) {
            throw new RuntimeException($newSchema->getUserInfo());
        }

        // The snmp_accountinfo table has a dynamic structure.
        // Only the static part is defined in the XML file. The additional
        // fields have to be preserved here.
        if (array_key_exists('snmp_accountinfo', $previousSchema['tables'])) {
            $newSchema['tables']['snmp_accountinfo']['fields'] = array_merge(
                $previousSchema['tables']['snmp_accountinfo']['fields'],
                $newSchema['tables']['snmp_accountinfo']['fields']
            );
        }

        $this->_logger->info('done.');
        return $newSchema;
    }

    /**
     * Update the table structure
     * @param array $previousSchema Current database definition
     * @param array $newSchema New database definition
     */
    public function updateSchema($previousSchema, $newSchema)
    {
        $this->_logger->info('Updating schema...');

        $result = $this->_schema->updateDatabase($newSchema, $previousSchema);
        if (PEAR::isError($result)) {
            throw new RuntimeException($result->getUserInfo());
        }

        $this->_logger->info('done');

        // Tables may have been added or removed, update list
        $this->_nada->clearCache();
        $this->_allTables = $this->_nada->getTables();

        // Tweak tables if necessary
        $this->tweakMySql();
    }

    /**
     * MySQL-specific tweaks on tables
     *
     * It's safe to call this even if another DBMS is used.
     */
    public function tweakMySql()
    {
        if (!$this->_nada->isMysql()) {
            return;
        }
        $this->_logger->info('Tweaking tables...');

        $engineInnoDb = array(
            'braintacle_windows',
            'controllers',
            'download_history',
            'drives',
            'inputs',
            'javainfo',
            'journallog',
            'memories',
            'modems',
            'monitors',
            'ports',
            'printers',
            'registry',
            'slots',
            'snmp_accountinfo',
            'snmp_communities',
            'softwares',
            'sounds',
            'storages',
            'videos',
            'virtualmachines',
        );
        $engineMemory = array(
            'conntrack',
            'engine_mutex',
            'prolog_conntrack',
        );

        foreach ($this->_managedTables as $name) {
            $table = $this->_allTables[$name];

            // Force UTF-8 for all tables to prevent charset conversion issues.
            $table->setCharset('utf8');

            // MDB2_Schema is not aware of MySQL's table engines and always
            // uses the configured default engine. The tables need to be
            // converted manually.
            if (in_array($name, $engineInnoDb)) {
                $engine = 'InnoDB';
            } elseif (in_array($name, $engineMemory)) {
                $engine = 'MEMORY';
            } else {
                $engine = 'MyISAM';
            }
            $table->setEngine($engine);
        }
        $this->_logger->info('done');
    }

    /**
     * Get list of column names from accountinfo table that need conversion.
     * @return array
     **/
    public function getUserdefinedInfoToConvert()
    {
        if ($this->_userDefinedInfoToConvert === null) {
            // Result not cached yet. Build list.
            if (!isset($this->_allTables['accountinfo'])) {
                // Database has not been populated yet. Nothing to do.
                $this->_userDefinedInfoToConvert = array();
            } else {
                // Get names of all columns except "hardware_id" and "tag".
                // These never need conversion.
                $columns = array();
                foreach ($this->_allTables['accountinfo']->getColumns() as $column) {
                    $name = $column->getName();
                    if ($name != 'hardware_id' and $name != 'tag') {
                        $columns[] = $name;
                    }
                }
                // If accountinfo_config table does not exist yet, it will be
                // created later. In that case, all columns need conversion.
                if (isset($this->_allTables['accountinfo_config'])) {
                    // Don't convert any column named fields_n where n matches
                    // an ID in accountinfo_config. These are already converted.
                    // Exception: fake date columns still need conversion.
                    $ids = $this->_db->fetchCol(
                        "SELECT id FROM accountinfo_config WHERE account_type = 'COMPUTERS' AND name != 'TAG'"
                    );
                    foreach ($ids as $id) {
                        $key = array_search("fields_$id", $columns);
                        if ($key !== false) {
                            $column = $this->_allTables['accountinfo']->getColumn("fields_$id");
                            if (!($column->getDatatype() == Nada::DATATYPE_VARCHAR and $column->getLength() == 10)) {
                                unset($columns[$key]);
                            }
                        }
                    }
                }
                // Store remaining list in cache.
                $this->_userDefinedInfoToConvert = $columns;
            }
        }
        return $this->_userDefinedInfoToConvert;
    }

    /**
     * Convert userdefined info to new format
     *
     * Old versions of Braintacle and OCS Inventory NG used the name of a
     * userdefined field directly as a column name in the accountinfo table,
     * causing unresolvable SQL issues. The names are now managed in the
     * accountinfo_config table, among some other configuration.
     **/
    public function convertUserdefinedInfo()
    {
        $db = $this->_db;
        $table = $this->_allTables['accountinfo'];
        $order = $db->fetchOne(
            "SELECT MAX(show_order) FROM accountinfo_config WHERE account_type = 'COMPUTERS'"
        );
        foreach ($this->getUserdefinedInfoToConvert() as $name) {
            $this->_logger->info('Converting userdefined column: ' . ucfirst($name));
            // Use transaction that can be rolled back should one operation fail
            $db->beginTransaction();
            $order += 1; // Append to the end
            $column = $table->getColumn($name);
            switch ($column->getDatatype()) {
                case Nada::DATATYPE_VARCHAR:
                    if ($column->getLength() == 10) {
                        // It's actually a date column with a nonstandard
                        // format. Convert data and change column datatype.
                        $this->_logger->info("Converting fake date column $name...");
                        $date = new Zend_Date;
                        // Use prepared statement for updating. $name is safe
                        // for SQL because this operation is only done on
                        // already converted columns where name is 'fields_n'.
                        $update = $db->prepare("UPDATE accountinfo SET $name = ? WHERE hardware_id = ?");
                        $result = $db->query("SELECT hardware_id, $name FROM accountinfo WHERE $name IS NOT NULL");
                        while ($row = $result->fetch(Zend_Db::FETCH_ASSOC)) {
                            if (empty($row[$name])) { // Empty string, convert to NULL
                                $newValue = null;
                            } else {
                                // Convert via Zend_Date object, causing invalid
                                // values to throw an exception
                                $date->set($row[$name], 'MM/dd/yyyy');
                                $newValue = $date->get('yyyy-MM-dd');
                            }
                            $update->execute(array($newValue, $row['hardware_id']));
                        }
                        $column->setDatatype(Nada::DATATYPE_DATE);
                        $this->_logger->info('done');
                        $type = Model_UserDefinedInfo::INTERNALTYPE_DATE;
                    } else {
                        $type = Model_UserDefinedInfo::INTERNALTYPE_TEXT;
                    }
                    break;
                case Nada::DATATYPE_INTEGER:
                case Nada::DATATYPE_FLOAT:
                    // These types are marked as text in accountinfo_config.
                    // They can still be distinguished by the column's datatype.
                    $type = Model_UserDefinedInfo::INTERNALTYPE_TEXT;
                    break;
                case Nada::DATATYPE_CLOB:
                    $type = Model_UserDefinedInfo::INTERNALTYPE_TEXTAREA;
                    break;
                case Nada::DATATYPE_BLOB:
                    $type = Model_UserDefinedInfo::INTERNALTYPE_BLOB;
                    break;
                case Nada::DATATYPE_DATE:
                    $type = Model_UserDefinedInfo::INTERNALTYPE_DATE;
                    break;
                default:
                    throw new UnexpectedValueException(
                        'Invalid datatype: ' . $column->getDatatype()
                    );
            }
            // Create entry in accountinfo_config and rename column if necessary
            // (do not process already converted columns, as can happen with
            // fake date columns)
            if (
                !preg_match('/^fields_([0-9]+)$/', $name, $matches) or
                $db->fetchOne('SELECT COUNT(id) FROM accountinfo_config WHERE id = ?', $matches[1]) == 0
            ) {
                $db->insert(
                    'accountinfo_config',
                    array(
                        'type' => $type,
                        'name' => ucfirst($name),
                        'show_order' => $order,
                        'account_type' => 'COMPUTERS'
                    )
                );
                // Rename column to fields_n
                $column->setName('fields_' . $db->lastInsertId('accountinfo_config', 'id'));
            }
            $db->commit();
        }
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
            $oldSchemaVersion = $this->_config->schemaVersion;
            if (is_null($oldSchemaVersion) or
                $oldSchemaVersion < self::SCHEMA_VERSION or
                count($this->getUserdefinedInfoToConvert()) > 0
            ) {
                return true;
            } else {
                return false;
            }
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
