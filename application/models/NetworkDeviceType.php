<?php
/**
 * Class representing a network device type
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
     * Construct an object from an Id
     *
     * @param integer $id ID of an existing type definition
     * @return Model_NetworkDeviceType
     * @throws RuntimeException if given ID id invalid
     **/
    public static function construct($id)
    {
        $type = self::createStatementStatic($id)->fetchObject(__CLASS__);
        if (!$type) {
            throw new RuntimeException('Invalid device type ID: ' . $id);
        }
        return $type;
    }

    /**
     * Generate statement to retrieve all network device types
     *
     * @param integer $id Return only given type. Default: return all types.
     * @return Zend_Db_Statement Statement
     **/
    public static function CreateStatementStatic($id=null)
    {
        $db = Model_Database::getAdapter();

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

        if ($id) {
            $select = $definedTypes;
            $select->where('devicetype.id = ?', $id);
        } else {
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

            // Create union of both queries (without duplicates)
            $select = $db->select()
                ->union(array($presentTypes, $definedTypes))
                ->order('description');
        }

        return $select->query();
    }

    /**
     * Add a type definition
     *
     * @param string $description Description of new type
     * @throws RuntimeException if a definition with the same description already exists.
     **/
    public static function add($description)
    {
        $db = Model_Database::getAdapter();
        if ($db->fetchOne('SELECT name FROM devicetype WHERE name = ?', $description)) {
            throw new RuntimeException('Network device type already exists: ' . $description);
        }
        $db->insert('devicetype', array('name' => $description));
    }

    /**
     * Rename a type definition
     *
     * @param string $description New description of type
     * @throws RuntimeException if a definition with the same description already exists.
     **/
    public function rename($description)
    {
        if ($description == $this->getDescription()) {
            return;
        }
        $db = Model_Database::getAdapter();
        if ($db->fetchOne('SELECT name FROM devicetype WHERE name = ?', $description)) {
            throw new RuntimeException('Network device type already exists: ' . $description);
        }
        $db->beginTransaction();
        $db->update(
            'devicetype',
            array('name' => $description),
            array('id = ?' => $this->getId())
        );
        $db->update(
            'network_devices',
            array('type' => $description),
            array('type = ?' => $this->getDescription())
        );
        $db->commit();
        $this->setDescription($description);
    }

    /**
     * Delete this type definition
     *
     * @throws RuntimeException if the type is still assigned to a visible device
     **/
    public function delete()
    {
        $db = Model_Database::getAdapter();
        if ($db->fetchOne(
            'SELECT type FROM network_devices WHERE type = ? AND macaddr NOT IN(SELECT macaddr FROM networks)',
            $this->getDescription()
        )) {
            throw new RuntimeException('Network device type still in use: ' . $this->getDescription());
        }
        $db->delete('devicetype', array('id = ?' => $this->getId()));
    }
}
