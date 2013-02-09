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
 *
 * Id may be NULL if a type is assigned to a device, but does not have a
 * definition. This is made possible by ocsreports and must be taken into
 * account when reading data. This is strongly discouraged and new code
 * should only assign defined types.
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
        $db = Model_Database::getAdapter();

        // Get all types that are assigned to a device. This may include
        // undefined types in which case 'id' will be NULL.
        $presentTypes = $db->select()
            ->from('network_devices', array())
            ->joinLeft(
                'devicetype',
                'devicetype.name = network_devices.type',
                array()
            )
            // Add columns manually to maintain explicit order required for UNION.
            ->columns('id', 'devicetype')
            ->columns(
                array(
                    'description' => 'type',
                    'num_devices' => new Zend_Db_Expr('COUNT(type)')
                ),
                'network_devices'
            )
            // Exclude stale entries where the interface has become part of an
            // inventoried computer.
            ->where('macaddr NOT IN(SELECT macaddr FROM networks)')
            ->group('devicetype.id')
            ->group('network_devices.type');

        // Get all defined types. This may include types that are not assigned
        // to any device in which case 'num_devices' will be 0.
        $definedTypes = $db->select()
            ->from('devicetype', array())
            // The JOIN condition excludes stale entries where the interface has
            // become part of an inventoried computer. If this was put in the
            // WHERE clause instead, types that are assigned only to stale
            // entries would not appear in the result at all.
            ->joinLeft(
                'network_devices',
                'devicetype.name = network_devices.type AND macaddr NOT IN(SELECT macaddr FROM networks)',
                array()
            )
            // Add columns manually to maintain explicit order required for UNION.
            ->columns(
                array(
                    'id',
                    'description' => 'name',
                ),
                'devicetype'
            )
            ->columns(
                array('num_devices' => new Zend_Db_Expr('COUNT(type)')),
                'devicetype'
            )
            ->group('devicetype.id')
            ->group('devicetype.name');

            // Return union of both queries (without duplicates)
            return $db->select()
                ->union(array($presentTypes, $definedTypes))
                ->order('description')
                ->query();
    }
}
