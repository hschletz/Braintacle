<?php
/**
 * Class representing an installed piece of software
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
 * An installed piece of software.
 *
 * Properties:
 *
 * - <b>Name</b> Name
 * - <b>RawName</b> Name as stored in the database, may contain non-UTF8 characters
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
        'RawName' => 'name',
        'Version' => 'version',
        'Publisher' => 'publisher',
        'Size' => 'filesize',
        'InstallLocation' => 'folder',
        'InstallationDate' => 'installdate',
        'Comment' => 'comments',
        'Guid' => 'guid',
        'Architecture' => 'bitswidth',
        'Language' => 'language',
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

    /** {@inheritdoc} */
    public function createStatement(
        $columns=null,
        $order=null,
        $direction='asc',
        $filters=null,
        $query=true
    )
    {
        $select = parent::createStatement($columns, $order, $direction, $filters, false);

        if (is_array($filters) and @$filters['Status'] == 'notIgnored') {
            $select->where(
                'softwares.name NOT IN(SELECT name FROM software_definitions WHERE display=FALSE)'
            );
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
                $value = \Zend\Filter\StaticFilter::execute($value, 'Library\FixEncodingErrors');
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
}
