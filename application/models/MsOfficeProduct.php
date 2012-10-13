<?php
/**
 * Class representing an MS Office product
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
 * - <b>ExtraDescription</b> Extra description for Office 2010
 * @package Models
 */
class Model_MsOfficeProduct extends Model_ChildObject
{
    /** {@inheritdoc} */
    protected $_propertyMap = array(
        // Values from 'officepack' table
        'Version' => 'officeversion',
        'Name' => 'product',
        'ProductId' => 'productid',
        'Architecture' => 'type',
        'ProductKey' => 'officekey',
        'Guid' => 'guid',
        'Install' => 'install', // Unknown, seems to be always 1. Only used for export.
        'ExtraDescription' => 'note',
    );

    /** {@inheritdoc} */
    protected $_types = array(
        'Version' => 'enum',
        'Architecture' => 'integer',
    );

    /** {@inheritdoc} */
    protected $_tableName = 'officepack';

    /** {@inheritdoc} */
    protected $_preferredOrder = 'Name';

}
