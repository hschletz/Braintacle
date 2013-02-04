<?php
/**
 * Class representing a network device type
 *
 * $Id$
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
 * @package Models
 */
/** A network device type definition
 *
 *
 * The following properties are available:
 *
 * - <b>Id:</b> ID
 * - <b>Description:</b> Description
 * - <b>Count:</b> Number of identified devices of this type
 * @package Models
 */
class Model_NetworkDeviceType extends Model_Abstract
{

    /** {@inheritdoc} */
    protected $_propertyMap = array(
        'Id' => 'id',
        'Description' => 'description',
        'Count' => 'num_devices',
    );

    /** {@inheritdoc} */
    protected $_types = array(
        'Id' => 'integer',
        'Count' => 'integer',
    );

    /**
     * Generate statement to retrieve all network device types
     *
     * @return Zend_Db_Statement Statement
     **/
    public static function CreateStatementStatic()
    {
        return Model_Database::getAdapter()->select()
            ->from(
                'network_devices',
                array(
                    'description' => 'type',
                    'num_devices' => new Zend_Db_Expr('COUNT(type)')
                )
            )
            // LEFT JOIN in case of missing type definitions. This can happen
            // because network_devices.type stores the name, not the id, and
            // ocsreports allows deleting the definition after the type is
            // assigned to a device.
            ->joinLeft(
                'devicetype',
                'devicetype.name = network_devices.type',
                array('id')
            )
            ->where('macaddr NOT IN(SELECT macaddr FROM networks)')
            ->group('type')
            ->group('devicetype.id')
            ->order('description')
            ->query();
    }
}
