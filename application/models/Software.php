<?php
/**
 * Class representing an installed piece of software
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
 */
/**
 * An installed piece of software.
 *
 * Properties:
 *
 * - <b>Name</b> Name
 * - <b>Version</b> Version
 * - <b>Publisher</b> Publisher/Manufacturer (Windows only)
 * - <b>Size</b> Size (Linux only)
 * - <b>InstallLocation</b> Installation directory (Windows only)
 * - <b>InstallationDate</b> Date of installation (not always available)
 * - <b>Comment</b> Comment
 * - <b>Architecture</b> 32/64 or NULL (Windows only)
 * - <b>Language</b> UI Language (Windows only, not always available)
 * - <b>Guid</b> GUID - may contain the MSI GIUD or arbitrary stuff (Windows only)
 * @package Models
 */
class Model_Software extends Model_ChildObject
{

    /** {@inheritdoc} */
    protected $_propertyMap = array(
        // Values from 'softwares' table
        'Name' => 'name',
        'Version' => 'version',
        'Publisher' => 'publisher',
        'Size' => 'filesize',
        'InstallLocation' => 'folder',
        'InstallationDate' => 'installdate',
        'Comment' => 'comments',
        'Guid' => 'guid',
        'Architecture' => 'bitswidth',
        'Language' => 'language',
        'NumComputers' => 'num_computers',
        'RawFilename' => 'filename', // Useless, always 'N/A' or NULL
        'RawSource' => 'source', // Unknown meaning
    );

    /** {@inheritdoc} */
    protected $_types = array(
        'Size' => 'integer',
        'InstallLocation' => 'clob',
        'Comment' => 'clob',
        'NumComputers' => 'integer',
        'InstallationDate' => 'date',
        'Architecture' => 'integer',
        'RawSource' => 'integer',
    );

    /** {@inheritdoc} */
    protected $_tableName = 'softwares';

    /** {@inheritdoc} */
    protected $_preferredOrder = 'Name';

    /**
     * Return a statement|select object with all objects matching criteria.
     * This class implements the following filters:
     * <b>Unique</b> filter that returns every name only once. Search parameter is ignored.
     * <b>Os</b> filter by OS type ('windows', 'other')
     */
    public function createStatement(
        $columns=null,
        $order=null,
        $direction='asc',
        $filters=null,
        $query=true
    )
    {
        // Don't pass NumComputers property to parent implementation.
        // This is added later if explicitly specified.
        if (empty($columns)) {
            $columns = array_keys($this->_propertyMap);
        }
        foreach ($columns as $column) {
            if ($column != 'NumComputers') {
                $columnNames[] = $column;
            }
        }

        // Have parent implementation do the basic stuff.
        $select = parent::createStatement($columnNames, $order, $direction, $filters, false);

        if (is_array($filters)) {
            foreach ($filters as $filter => $search) {
                switch ($filter) {
                    case 'Unique':
                        if (in_array('NumComputers', $columns)) {
                            $select->columns(array('num_computers' => '(COUNT(DISTINCT hardware_id))'));
                            $select->group('softwares.name');
                        } else {
                            $select->distinct();
                        }
                        break;
                    case 'Os':
                        switch ($search) {
                            case 'windows':
                                $select->join(
                                    'hardware',
                                    'hardware.id = softwares.hardware_id AND hardware.osname LIKE \'%Windows%\'',
                                    array() // no columns, just filter
                                );
                                break;
                            case 'other':
                                $select->join(
                                    'hardware',
                                    'hardware.id = softwares.hardware_id AND hardware.osname NOT LIKE \'%Windows%\'',
                                    array() // no columns, just filter
                                );
                                break;
                        }
                        break;
                    case 'Status':
                        switch ($search) {
                            case 'accepted':
                                $select->where(
                                    'softwares.name IN(SELECT extracted FROM dico_soft)'
                                );
                                break;
                            case 'ignored':
                                $select->where(
                                    'softwares.name IN(SELECT extracted FROM dico_ignored)'
                                );
                                break;
                            case 'new':
                                $select->where(
                                    'softwares.name NOT IN(SELECT extracted FROM dico_ignored)'
                                );
                                $select->where(
                                    'softwares.name NOT IN(SELECT extracted FROM dico_soft)'
                                );
                                break;
                            case 'all':
                                break;
                            case 'notIgnored':
                                $select->where(
                                    'softwares.name NOT IN (SELECT extracted FROM dico_ignored)'
                                );
                        }
                        break;
                }
            }
        }

        if ($query) {
            return $select->query();
        } else {
            return $select;
        }
    }

    /**
     * Retrieve a property by its logical name
     *
     * Mangles certain characters in software names to valid UTF-8.
     * Strips trailing slashes from InstallLocation.
     */
    function getProperty($property, $rawValue=false)
    {
        $value = parent::getProperty($property, $rawValue);
        if ($rawValue) {
            return $value;
        }

        switch ($property) {
            case 'Name':
                $value = self::mangleName($value);
                break;
            case 'InstallLocation':
                // Strip trailing slashes
                $value = rtrim($value, '/');
                break;
            case 'Architecture':
                // Convert 0 to NULL
                if ($value == 0) {
                    $value = null;
                }
                break;
        }
        return $value;
    }

    /**
     * Blacklist a piece of software, i.e. mark it for not being displayed.
     * @param string $name Raw name
     */
    static function ignore($name)
    {
        $db = Model_Database::getAdapter();

        if (is_null($name)) { // Inserting NULL would fail
            $name = '';
        }
        $db->delete('dico_soft', array('extracted=?' => $name));
        $db->insert('dico_ignored', array('extracted' => $name));
    }

    /**
     * Whitelist a piece of software, i.e. mark it for being known and accepted.
     * @param string $name Raw name
     */
    static function accept($name)
    {
        $db = Model_Database::getAdapter();

        if (is_null($name)) { // Inserting NULL would fail
            $name = '';
        }
        $db->delete('dico_ignored', array('extracted=?' => $name));
        $db->insert(
            'dico_soft',
            array(
                'extracted' => $name,
                'formatted' => $name
            )
        );
    }

    /**
     * Static helper which mangles certain characters in software names to valid UTF-8.
     * @param string $name Raw name
     * @return string UTF8-compliant name
     */
    static function mangleName($name)
    {
        // Fix invalid representation of (TM) symbol
        return str_replace(
            chr(0xc2).chr(0x99),
            chr(0xe2).chr(0x84).chr(0xa2),
            $name
        );
    }

}
