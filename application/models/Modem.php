<?php
/**
 * Class representing a modem
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
 * A modem
 *
 * Properties:
 * - <b>Type</b>
 * - <b>Name</b>
 * @package Models
 */
class Model_Modem extends Model_ChildObject
{
    protected $_propertyMap = array(
        // Values from 'modems' table
        'Type' => 'type',
        'Name' => 'name',
    );
    protected $_xmlElementName = 'MODEMS';
    protected $_xmlElementMap = array(
        'DESCRIPTION' => null,
        'MODEL' => null,
        'NAME' => 'Name',
        'TYPE' => 'Type',
    );
    protected $_tableName = 'modems';
    protected $_preferredOrder = 'Type';

}
