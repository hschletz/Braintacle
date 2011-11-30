<?php
/**
 * Interface for database schema management
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
 * @package Library
 * @filesource
 */
/**
 * Includes
 */
require_once('Braintacle/MDB2.php');
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
    const SCHEMA_VERSION = 2;

    /**
     * MDB2_Schema object
     * @var MDB2_Schema
     */
    protected $_schema;

    /**
     * Zend_Log object
     * @var Zend_Log
     */
    protected $_logger;

    /**
     * List of all tables in the database
     * @var array
     */
    protected $_allTables;

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
        $this->_allTables = $this->_schema->db->manager->listTables();
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
        $mdb2 = $this->_schema->db;

        // Since OCS Inventory only supports MySQL, databases for any other DBMS
        // will essentially be managed exclusively by Braintacle so that this
        // operation will not be necessary.
        if ($mdb2->dbsyntax != 'mysql') {
            return;
        }

        $this->_logger->info('Fixing keys...');

        // The definitions for some tables require an extra constraint to be
        // created before the bad PK can be dropped.
        $createConstraint = array(
            'journallog',
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
        $fixTables = array_intersect($fixTables, $this->_allTables);

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
        $name = $this->_schema->db->getDatabase();
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
        $this->_allTables = $this->_schema->db->manager->listTables();

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
        $mdb2 = $this->_schema->db;
        if ($mdb2->dbsyntax != 'mysql') {
            return;
        }
        $this->_logger->info('Tweaking tables...');

        $engineInnoDb = array(
            'accesslog',
            'accountinfo',
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

        foreach ($this->_allTables as $table) {
            // Force UTF-8 for all tables to prevent charset conversion issues.
            $mdb2->exec("ALTER TABLE $table CHARACTER SET utf8");

            // MDB2_Schema is not aware of MySQL's table engines and always
            // uses the configured default engine. The tables need to be
            // converted manually.
            if (in_array($table, $engineInnoDb)) {
                $engine = 'InnoDB';
            } elseif (in_array($table, $engineMemory)) {
                $engine = 'MEMORY';
            } else {
                $engine = 'MyISAM';
            }
            $mdb2->exec("ALTER TABLE $table ENGINE = $engine");
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

        $mdb2 = $this->_schema->db;

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

            // Remove all 'insert' commands from 'operators' initialization to
            // prevent re-creation of default admin account
            if ($name == 'operators') {
                $commands = array();
                foreach ($table['initialization'] as $command) {
                    if ($command['type'] != 'insert') {
                        $commands[] = $command;
                    }
                }
                $table['initialization'] = $commands;
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
            foreach ($mdb2->reverse->tableInfo($name) as $column) {
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
                $query = 'SELECT COUNT(*) FROM ' .
                        $mdb2->quoteIdentifier($name) .
                        ' WHERE ';
                $i = 1;
                foreach ($fieldlist as $fieldname => $value) {
                    $query .= $mdb2->quoteIdentifier($fieldname);
                    $query .= '=';
                    $query .= $mdb2->quote($value, $constraints[$fieldname]);
                    if ($i < $count)
                        $query .= ' OR ';
                    $i++;
                }
                $result = $mdb2->query($query);

                if ($result->fetchOne()) {
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
        $this->convertPasswords();
    }

    /**
     * Convert default cleartext password
     *
     * Braintacle does not support cleartext passwords. Old databases for which
     * the password for the default 'admin' account has never been changed may
     * contain a cleartext password so that logging in would be impossible. It
     * will be converted to its MD5 hash.
     */
    public function convertPasswords()
    {
        $numRows = $this->_schema->db->exec(
            "UPDATE operators SET passwd='21232f297a57a5a743894a0e4a801fc3' WHERE passwd='admin'"
        );
        if ($numRows) {
            $this->_logger->warn(
                'Account with default password detected. It has been hashed, but should be changed!'
            );
        }
    }

    /**
     * Check for database update requirement
     *
     * This method evaluates the SchemaVersion option. If it is not present or
     * less than {@link SCHEMA_VERSION}, a database update is required.
     * @return bool TRUE if update is required.
     */
    public function isUpdateRequired()
    {
        // Check for presence of 'config' table first
        if (in_array('config', $this->_allTables)) {
            $oldSchemaVersion = Model_Config::get('SchemaVersion');
            if (is_null($oldSchemaVersion) or $oldSchemaVersion < self::SCHEMA_VERSION) {
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
        if ($this->_schema->db->dbsyntax != 'mysql') {
            return false;
        }
        // Empty database (i.e. without 'config' table) is considered compatible
        // because it might as well get populated via ocsreports.
        if (!in_array('config', $this->_allTables)) {
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
