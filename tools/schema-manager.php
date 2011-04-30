#!/usr/bin/php
<?php
/**
 * Update the database schema and adjust some data to the new schema.
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
 * @package Tools
 */
/**
 * This script updates Braintacle's database schema using MDB2_Schema.
 * In addition to MDB2_Schema's builtin functionality, some table content
 * (like for the 'config' table) is updated too.
 * The tables are set to the state represented by the XML files in the "schema"
 * directory.
 *
 * Run this script every time the schema has changed. It is safe to run it more
 * than once, even if the schema has not changed. However, it won't hurt to
 * back up your database first.
 */

// All paths are relative to this script's parent directory
$basepath = realpath(dirname(dirname(__FILE__)));

// Prepend PEAR and Zend directories to include path
set_include_path(
    implode(
        PATH_SEPARATOR,
        array(
            realpath("$basepath/library"),
            realpath("$basepath/library/PEAR"),
            get_include_path()
        )
    )
);

// Force argument to be specified
if (count($_SERVER['argv']) != 2) {
    print <<<EOT

    USAGE:

    schema-manager.php production|staging|testing|development

    If unsure, choose 'production'.

EOT;
    exit(1);
}

// Set Zend application constants
define('APPLICATION_PATH', realpath("$basepath/application"));
define('APPLICATION_ENV', $_SERVER['argv'][1]);

// Create MDB2 object
require_once('Braintacle/MDB2.php');
Braintacle_MDB2::setErrorReporting();
$mdb2 = Braintacle_MDB2::factory();

// Create MDB2_Schema object.
require_once('MDB2/Schema.php');
$options = array (
    'quote_identifier' => true,
    'force_defaults' => false,
    'portability' => MDB2_PORTABILITY_ALL ^ MDB2_PORTABILITY_EMPTY_TO_NULL,
);
$schema = MDB2_Schema::factory($mdb2, $options);


// The schema is split into multiple files which MDB2_Schema cannot handle.
// We have to concatenate them into a single file first.
$temp = tmpfile();
if (!$temp) {
    print "Could not create temporary file\n";
    exit(1);
}

$header = <<<EOT
<?xml version="1.0" encoding="UTF-8" ?>
<database>
 <name>ocsweb</name>
 <create>true</create>
 <overwrite>false</overwrite>

EOT;
if (!fwrite($temp, $header)) {
    print "Error writing to temporary file\n";
    exit(1);
}

$dir = opendir("$basepath/schema");
if (!$dir) {
    print "Can't get a handle for directory '$basepath/schema'\n";
    exit(1);
}

while (($file = readdir($dir)) !== false ) {
    if (substr($file, -4) != '.xml')
        continue;
    $content = file_get_contents("$basepath/schema/$file");
    if ($content == false) {
        print "Error reading $file\n";
        exit(1);
    }
    if (!fwrite($temp, $content)) {
        print "Error writing to temporary file\n";
        exit(1);
    }
}

if (!fwrite($temp, "</database>\n")) {
    print "Error writing to temporary file\n";
    exit(1);
}


// Parse XML definition
print 'Parsing XML definition...';
$meta = stream_get_meta_data($temp);
$newSchema = $schema->parseDatabaseDefinitionFile($meta['uri']);
fclose($temp);
if (PEAR::isError($newSchema)) {
    print "\n" . $newSchema->getUserInfo() . "\n";
    exit(1);
}
print " done\n";

// Change hardcoded database name to the name from the config file
// The get_object_vars() quirk is only necessary to satisfy Zend coding standards.
$properties = get_object_vars($mdb2);
$newSchema['name'] = $properties['database_name'];

// An unmodified OCS Inventory database would make getDefinitionFromDatabase()
// choke on some weird primary keys. These keys need to be dropped and
// re-created correctly before the schema can be validated by MDB2_Schema.
// See schema/README.html for details.
if ($mdb2->phptype == 'mysql') {
    $mdb2->loadModule('Manager');
    $mdb2->loadModule('Reverse', null, true);

    // List of tables to be checked and fixed
    // Only existing tables can be processed.
    $fixTables = array_intersect(
        array(
            'accesslog',
            'blacklist_macaddresses',
            'blacklist_serials',
            'blacklist_subnet',
            'controllers',
            'drives',
            'hardware',
            'inputs',
            'journallog',
            'memories',
            'modems',
            'monitors',
            'networks',
            'ports',
            'printers',
            'registry',
            'slots',
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
            'softwares',
            'sounds',
            'storages',
            'videos',
            'virtualmachines',
        ),
        $mdb2->manager->listTables()
    );

    // Template for constraint creation
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
        if (count($fields) > 1 or !isset($fields['id'])) {
            print 'Fixing primary key for table ' . $table . '...';

            // The definitions for some tables require an extra constraint
            // before the PK can be dropped.
            switch ($table) {
                case 'journallog':
                case 'snmp_cards':
                case 'snmp_cartridges':
                case 'snmp_cpus':
                case 'snmp_drives':
                case 'snmp_fans':
                case 'snmp_inputs':
                case 'snmp_localprinters':
                case 'snmp_memories':
                case 'snmp_modems':
                case 'snmp_networks':
                case 'snmp_ports':
                case 'snmp_powersupplies':
                case 'snmp_softwares':
                case 'snmp_sounds':
                case 'snmp_storages':
                case 'snmp_switchs':
                case 'snmp_trays':
                case 'snmp_videos':
                case 'virtualmachines':
                    $mdb2->manager->createConstraint($table, 'primary', $templateUnique);
            }

            // Drop the bad PK and create a new one.
            $mdb2->manager->dropConstraint($table, 'primary', true);
            $mdb2->manager->createConstraint($table, 'primary', $templatePrimary);
            print " done\n";
        }
    }
}

// Get existing schema from database
print 'retrieving existing definition from database...';
$previousSchema = $schema->getDefinitionFromDatabase();
if (PEAR::isError($previousSchema)) {
    print "\n" . $previousSchema->getUserInfo() . "\n";
    exit(1);
}
print " done\n";


// The accountinfo table has a dynamic structure.
// Only the static part is defined in the XML file.
// The additional fields have to be preserved here.
if (array_key_exists('accountinfo', $previousSchema['tables'])) {
    $newSchema['tables']['accountinfo']['fields'] = array_merge(
        $previousSchema['tables']['accountinfo']['fields'],
        $newSchema['tables']['accountinfo']['fields']
    );
}


// Update the schema
if ($schema->db->getOption('use_transactions')) {
    $schema->db->beginNestedTransaction();
}

print 'Updating schema...';
$schema->updateDatabase($newSchema, $previousSchema);
print " done\n";


// Table initialization is only done when the table is created for the first time.
// In case of an upgrade we have to initialize the tables manually.
foreach ($newSchema['tables'] as $name => $table) {
    if (!array_key_exists($name, $previousSchema['tables']) // just created, already initialized
        or empty($table['initialization']) // nothing to do for this table
    ) {
        continue;
    }

    // Remove all 'insert' commands from operators initialization to prevent re-creation of default admin account
    if ($name == 'operators') {
        foreach ($table['initialization'] as $command) {
            if ($command['type'] != 'insert') {
                $commands[] = $command;
            }
        }
        if (isset($commands)) {
            $table['initialization'] = $commands;
        } else {
            $table['initialization'] = array();
        }
    }

    // We have to avoid duplicate entries that would violate primary keys or
    // unique constraints.
    // Therefore we have to find a way to identify existing rows and determine
    // whether the row to be inserted would violate a constraint.
    // This is far from perfect yet because we will only detect single-column
    // primary keys and single-column unique constraints. Furthermore we assume
    // only simple inserts. Complex inserts like insert/select will produce
    // unpredictable results!
    $constraints = array();
    foreach ($schema->db->reverse->tableInfo($name) as $column) {
        if (strpos($column['flags'], 'primary_key') !== false
            or strpos($column['flags'], 'unique_key') !== false
        ) {
            $constraints[$column['name']] = $column['mdb2type'];
        }
    }
    if (empty($constraints)) {
        // Without any constraint we would produce more and more entries on every upgrade.
        // Bad database design! Abort.
        print "FATAL: Cannot reliably initialize table '$name' because the table has no constraints.\n";
        exit (1);
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
            print "FATAL: Cannot reliably initialize table '$name' with unconstrained data.\n";
            exit (1);
        }

        // Check for existing rows that would prevent successful insertion.
        $query = 'SELECT COUNT(*) FROM ' .
                 $schema->db->quoteIdentifier($name) .
                 ' WHERE ';
        $i = 1;
        foreach ($fieldlist as $fieldname => $value) {
            $query .= $schema->db->quoteIdentifier($fieldname);
            $query .= '=';
            $query .= $schema->db->quote($value, $constraints[$fieldname]);
            if ($i < $count)
                $query .= ' OR ';
            $i++;
        }
        $result = $schema->db->query($query);

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
    print "Initializing table '$name'...";
    $schema->initializeTable($name, $table);
    print ' done (';
    print count($table['initialization']) . ' rows inserted/updated, ';
    print count($skipRows) . ' rows skipped';
    print ")\n";
}


// Success.
if ($schema->db->getOption('use_transactions')) {
    $schema->db->completeNestedTransaction();
}
print "Schema successfully updated.\n";


// MDB2_Schema is not aware of MySQL's table engines and always uses the
// configured default engine. The tables need to be converted manually.
if ($mdb2->phptype == 'mysql') {
    foreach ($mdb2->manager->listTables() as $table) {
        switch ($table) {
            case 'blacklist_macaddresses':
            case 'blacklist_serials':
            case 'braintacle_blacklist_assettags':
            case 'config':
            case 'deleted_equiv':
            case 'deploy':
            case 'devicetype':
            case 'dico_ignored':
            case 'dico_soft':
            case 'download_affect_rules':
            case 'download_servers':
            case 'engine_persistent':
            case 'files':
            case 'groups':
            case 'groups_cache':
            case 'hardware_osname_cache':
            case 'itmgmt_comments':
            case 'network_devices':
            case 'operators':
            case 'regconfig':
            case 'registry_name_cache':
            case 'registry_regvalue_cache':
            case 'softwares_name_cache':
            case 'subnet':
            case 'tags':
                $engine = 'MyISAM';
                break;
            case 'accesslog':
            case 'accountinfo':
            case 'bios':
            case 'controllers':
            case 'devices':
            case 'download_available':
            case 'download_enable':
            case 'download_history':
            case 'drives':
            case 'hardware':
            case 'inputs':
            case 'javainfo':
            case 'memories':
            case 'modems':
            case 'monitors':
            case 'netmap':
            case 'networks':
            case 'ports':
            case 'printers':
            case 'registry':
            case 'slots':
            case 'softwares':
            case 'sounds':
            case 'storages':
            case 'videos':
            case 'virtualmachines':
                $engine = 'InnoDB';
                break;
            case 'conntrack':
            case 'engine_mutex':
            case 'locks':
            case 'prolog_conntrack';
                $engine = 'MEMORY';
                break;
            default:
                $engine = null;
                print "WARNING: No engine defined for table '$table'. Engine will not be changed.\n";
        }
        if ($engine) {
            $mdb2->exec("ALTER TABLE $table ENGINE = $engine");
        }
    }
}

// Braintacle does not support cleartext passwords.
// Old databases for which the password for the default 'admin' account has
// never been changed may contain a cleartext password so that logging in would
// be impossible. It will be converted to its MD5 hash.
if ($mdb2->exec("UPDATE operators SET passwd='21232f297a57a5a743894a0e4a801fc3' WHERE passwd='admin'")) {
    print "\nWARNING: Account with default password detected.\n";
    print "It has been hashed, but should be changed!\n";
}

$schema->disconnect();
