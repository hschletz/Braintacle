<?php
/**
 * Interface for database schema management
 *
 * Copyright (C) 2011-2013 Holger Schletz <holger.schletz@web.de>
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
require_once('MDB2/Schema.php');
/**
 * Interface for database schema management
 *
 * This class contains all functionality to manage the database schema and its
 * statically initialized data. Progress is communicated via Zend_Log. The
 * simplest usage is a 3-step process:
 *
 * 1. Set up a Zend_Log object.
 * 2. Pass it to the constructor of a new Braintacle_SchemaManager object.
 * 3. Call its {@link updateAll()} method.
 *
 * That's it! For mor control over the update process, the other methods can be
 * called manually instead of {@link updateAll()}.
 * @package Library
 */
class Braintacle_SchemaManager
{
    /**
     * Latest version of the database schema
     */
    const SCHEMA_VERSION = 6;

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
     * @param Zend_Log Logger object
     * @param MDB2_Driver_Common Database connection (default: connect automatically)
     */
    function __construct(Zend_Log $logger, MDB2_Driver_Common $mdb2=null)
    {
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
        $this->updateData($previousSchema, $newSchema);
        $this->convertUserdefinedInfo();
        Model_Config::set('SchemaVersion', self::SCHEMA_VERSION);
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
            'accesslog',
            'blacklist_macaddresses',
            'blacklist_serials',
            'blacklist_subnet',
            'controllers',
            'drives',
            'hardware',
            'inputs',
            'memories',
            'modems',
            'monitors',
            'networks',
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
            if (substr($file, -4) != '.xml')
                continue; // Ignore files without .xml suffix
            $content = file_get_contents("$this->_basepath/schema/$file");
            if ($content == false) {
                throw new RuntimeException("Error reading $file");
            }
            if (!fwrite($temp, $content)) {
                throw new RuntimeException('Error writing to temporary file');
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

        // The accountinfo and snmp_accountinfo tables have a dynamic structure.
        // Only the static part is defined in the XML file. The additional
        // fields have to be preserved here.
        foreach (array('accountinfo', 'snmp_accountinfo') as $table) {
            if (array_key_exists($table, $previousSchema['tables'])) {
                $newSchema['tables'][$table]['fields'] = array_merge(
                    $previousSchema['tables'][$table]['fields'],
                    $newSchema['tables'][$table]['fields']
                );
            }
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
            'accountinfo',
            'accountinfo_config',
            'bios',
            'controllers',
            'devices',
            'download_available',
            'download_enable',
            'download_history',
            'drives',
            'hardware',
            'inputs',
            'javainfo',
            'journallog',
            'memories',
            'modems',
            'monitors',
            'netmap',
            'networks',
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
            'locks',
            'prolog_conntrack',
        );

        foreach ($this->_allTables as $name => $table) {
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
     * Initialize and convert content
     * @param array $previousSchema Current database definition
     * @param array $newSchema New database definition
     */
    public function updateData($previousSchema, $newSchema)
    {
        $this->_logger->info('Updating data...');

        // Table initialization is only done when the table is created for the
        // first time. In case of an upgrade the tables need to be initialized
        // manually.
        foreach ($newSchema['tables'] as $name => $table) {
            if (!array_key_exists($name, $previousSchema['tables'])) {
                // just created, already initialized
                continue;
            }
            if (empty($table['initialization'])) {
                // nothing to do for this table
                continue;
            }

            // Avoid duplicate entries that would violate primary keys or
            // unique constraints. Identify existing rows and determine whether
            // the row to be inserted would violate a constraint.
            // Detection works only on single-column keys/constraints and simple
            // inserts (no insert/select - results would be unpredictable!)

            // Some tables with autoincrement fields are initialized with static
            // data. Autoincrement fields should not be set manually because
            // this would clash with the internal increment counter. Without a
            // distinct value in the initialization data there is no way to
            // reliably identify a row.
            // These tables will just be skipped here, i.e. they only get
            // initialized upon creation. This also prevents possible problems
            // when the automatically generated value is referenced inside the
            // static initialization of a foreign table. The foreign key might
            // be incorrect then if a referenced row has been inserted later.
            if (
                $name == 'accountinfo_config'
                or $name == 'downloadwk_fields'
                or $name == 'downloadwk_tab_values'
                or $name == 'downloadwk_statut_request'
            ) {
                continue;
            }

            // Get constraints for this table
            $constraints = array();
            foreach ($this->_schema->db->reverse->tableInfo($name) as $column) {
                if (strpos($column['flags'], 'primary_key') !== false
                    or strpos($column['flags'], 'unique_key') !== false
                ) {
                    $constraints[$column['name']] = $column['mdb2type'];
                }
            }
            if (empty($constraints)) {
                // Without any constraint more and more entries would be
                // generated on every upgrade. Bad database design!
                throw new RuntimeException("FATAL: table '$name' has no constraints.");
            }

            // Find rows to be inserted that don't already exist.
            $skipRows = array();
            foreach ($table['initialization'] as $commandIndex => $command) {
                if ($command['type'] != 'insert') {
                    continue;
                }

                // Build the list of fields to be checked for existent values
                $fieldlist = array();
                foreach ($command['data']['field'] as $field) {
                    if (array_key_exists($field['name'], $constraints)) {
                        $fieldlist[$field['name']] = $field['group']['data'];
                    }
                }
                $count = count($fieldlist);
                if ($count == 0) {
                    throw new RuntimeException("FATAL: Unconstrained data for table '$name'");
                }

                // Check for existing rows that would prevent successful insertion.
                $query = $this->_db->select()->from($name, 'COUNT(*)');
                foreach ($fieldlist as $fieldname => $value) {
                    $query->orWhere("$fieldname = ?", $value);
                }
                if ($query->query()->fetchColumn()) {
                    // Found existing row.
                    $skipRows[] = $commandIndex;
                }

            }
            // Sort results in reverse order to prevent index shifting while removing them.
            rsort($skipRows, SORT_NUMERIC);
            foreach ($skipRows as $index) {
                unset ($table['initialization'][$index]);
            }
            $this->_logger->info("Initializing table '$name'...");
            $this->_schema->initializeTable($name, $table);
            $this->_logger->info(
                'done (' .
                count($table['initialization']) .
                ' rows inserted/updated, ' .
                count($skipRows) .
                ' rows skipped)'
            );
        }

        $this->setupAccounts();
    }

    /**
     * Setup user accounts
     *
     * This will create a default account if no account exists yet, and convert
     * a cleartext password for the default account.
     */
    public function setupAccounts()
    {
        $db = $this->_schema->db;

        // If no account exists yet, create a default account.
        if ($db->queryOne('SELECT COUNT(id) FROM operators') === '0') {
            Model_Account::create(array('Id' => 'admin'), 'admin');
            $this->_logger->notice(
                'Default account \'admin\' created with password \'admin\'.'
            );
        }

        // Braintacle does not support cleartext passwords. Old databases for
        // which the password for the default 'admin' account has never been
        // changed may contain a cleartext password so that logging in would be
        // impossible. It will be converted to its MD5 hash.
        $numRows = $db->exec(
            "UPDATE operators SET passwd='21232f297a57a5a743894a0e4a801fc3' WHERE passwd='admin'"
        );
        if ($numRows) {
            $this->_logger->info(
                'Cleartext password hashed.'
            );
        }

        // Warn about default password 'admin'
        if ($db->queryOne(
            "SELECT COUNT(id) FROM operators WHERE passwd = '21232f297a57a5a743894a0e4a801fc3'"
        ) !== '0') {
            $this->_logger->warn(
                'Account with default password detected. It should be changed as soon as possible!'
            );
        }
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
                        'id_tab' => 1, // default
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
            $oldSchemaVersion = Model_Config::get('SchemaVersion');
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
        // Empty database (i.e. without 'config' table) is considered compatible
        // because it might as well get populated via ocsreports.
        if (!isset($this->_allTables['config'])) {
            return true;
        }
        // The SchemaVersion option will be NULL for databases not managed by
        // Braintacle.
        if (is_null(Model_Config::get('SchemaVersion'))) {
            return true;
        }
        // SchemaVersion present => database has previously been managed by
        // Braintacle.
        return false;
    }

}
