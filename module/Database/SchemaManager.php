<?php
/**
 * Schema management class
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
 */

Namespace Database;

/**
 * Schema management class
 *
 * This class contains all functionality to manage the database schema and to
 * initialize and migrate data.
 *
 * @codeCoverageIgnore
 */
class SchemaManager
{
    /**
     * Legacy schema manager passed to the constructor
     * @var \Braintacle_SchemaManager
     */
    protected $_legacySchemaManager;

    /**
     * Database adapter
     * @var \Zend\Db\Adapter
     */
    protected $_db;

    /**
     * NADA interface
     * @var \Nada_Database
     */
     protected $_nada;

    /**
     * Constructor
     *
     * @param \Braintacle_SchemaManager $legacySchemaManager Legacy schema manager
     */
    function __construct(\Braintacle_SchemaManager $legacySchemaManager)
    {
        $this->_legacySchemaManager = $legacySchemaManager;
        $this->_db = \Library\Application::getService('Db');
        $this->_nada = \Library\Application::getService('Database\Nada');
    }

    /**
     * Update database automatically
     *
     * This is the simplest way to update the database. It performs all
     * necessary steps to update the database schema and migrate data.
     */
    public function updateAll()
    {
        $this->updateTables();
        $this->_legacySchemaManager->updateAll();
    }

    /**
     * Create/update all tables
     *
     * This method iterates over all JSON schema files in ./data, instantiates
     * table objects of the same name for each file and calls their setSchema()
     * method.
     */
    public function updateTables()
    {
        $glob = new \GlobIterator(Module::getPath('data/Tables') . '/*.json');
        foreach ($glob as $fileinfo) {
            $tableClass = $fileinfo->getBaseName('.json');
            $table = \Library\Application::getService('Database\Table\\' . $tableClass);
            $table->setSchema();
        }
        // Views need manual invocation.
        \Library\Application::getService('Database\Table\PackageDownloadInfo')->setSchema();
    }
}
