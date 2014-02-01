<?php
/**
 * Class representing an MS Office product
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
 * @package Models
 */
/**
 * An MS Office Product
 *
 * Properties:
 *
 * - <b>Version</b> 97, 2000, XP, 2003, 2007 or 2010
 * - <b>Name</b> Full product name
 * - <b>ProductId</b> Individual product ID
 * - <b>Architecture</b> 32 or 64 (Bit)
 * - <b>ProductKey</b> Product key
 * - <b>Guid</b> GUID used by Windows Installer
 * - <b>Type</b> TYPE_INSTALLED_PRODUCT or TYPE_UNUSED_LICENSE
 * - <b>ExtraDescription</b> Extra description for Office 2010
 * @package Models
 */
class Model_MsOfficeProduct extends Model_ChildObject
{
    /**
     * 'Type' property for an unused license (leftover from an uninstalled product)
     **/
    const TYPE_UNUSED_LICENSE = 0;

    /**
     * 'Type' property for a regular installed product
     **/
    const TYPE_INSTALLED_PRODUCT = 1;

    /** {@inheritdoc} */
    protected $_propertyMap = array(
        // Values from 'officepack' table
        'Version' => 'officeversion',
        'Name' => 'product',
        'ProductId' => 'productid',
        'Architecture' => 'type',
        'ProductKey' => 'officekey',
        'Guid' => 'guid',
        'Type' => 'install',
        'ExtraDescription' => 'note',
    );

    /** {@inheritdoc} */
    protected $_types = array(
        'Version' => 'enum',
        'Architecture' => 'integer',
        'Type' => 'enum', // one of the TYPE_* constants
    );

    /** {@inheritdoc} */
    protected $_tableName = 'officepack';

    /** {@inheritdoc} */
    protected $_preferredOrder = 'Name';


    /**
     * Return a statement|select object with all objects matching criteria.
     * This class implements the 'Type' filter which selects only items with the
     * given 'Type' property.
     */
    public function createStatement(
        $columns=null,
        $order=null,
        $direction='asc',
        $filters=null,
        $query=true
    )
    {
        $select = parent::createStatement($columns, $order, $direction, $filters, false);

        if (is_array($filters) and isset($filters['Type'])) {
            $select->where('install = ?', $filters['Type']);
        }

        if ($query) {
            return $select->query();
        } else {
            return $select;
        }
    }

}
