<?php
/**
 * Class representing an extension slot
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
 * @package Models
 * @filesource
 */
/**
 * An extension slot (PCI, PCIe, AGP, ISA, PCMCIA...)
 *
 * Properties:
 * - <b>Name</b>
 * - <b>Status</b>
 * @package Models
 */
class Model_ExtensionSlot extends Model_ChildObject
{
    protected $_propertyMap = array(
        // Values from 'slots' table
        'Name' => 'designation',
        'Status' => 'status',
        'RawName' => 'name', // Useless, only 'System Slot' or translation of it
        'RawDescription' => 'description', // Useless, duplicate of RawName
        'RawPshare' => 'pshare', // obsolete
        'RawPurpose' => 'purpose', // always NULL
    );
    protected $_xmlElementName = 'SLOTS';
    protected $_xmlElementMap = array(
        'DESCRIPTION' => 'RawDescription',
        'DESIGNATION' => 'Name',
        'NAME' => 'RawName',
        'PSHARE' => null, // obsolete
        'PURPOSE' => 'RawPurpose',
        'STATUS' => 'Status',
    );
    protected $_tableName = 'slots';
    protected $_preferredOrder = 'Name';

}
