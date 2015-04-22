<?php
/**
 * Client item manager
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

namespace Model\Client;

/**
 * Client item manager
 *
 * Item types are named by their corresponding model class (without prefix).
 */
class ItemManager
{
    /**
     * Map of item types to table classes
     * @var string[]
     */
    protected $_tableClasses = array(
        'AudioDevice' => 'AudioDevices',
        'Modem' => 'Modems',
        'Printer' => 'Printers',
    );

    /**
     * Default properties to sort by
     * @var string[]
     */
    protected $_defaultOrder = array(
        'AudioDevice' => 'Manufacturer',
        'Modem' => 'Type',
        'Printer' => 'Name',
    );

    /**
     * Service manager
     * @var \Zend\ServiceManager\ServiceManager
     */
    protected $_serviceManager;

    /**
     * Constructor
     *
     * @param \Zend\ServiceManager\ServiceManager $serviceManager
     */
    public function __construct(\Zend\ServiceManager\ServiceManager $serviceManager)
    {
        $this->_serviceManager = $serviceManager;
    }

    /**
     * Get table gateway for given type
     *
     * @param string $type Item type
     * @return \Database\AbstractTable
     * @throws \InvalidArgumentException if $type is not defined
     */
    public function getTable($type)
    {
        if (!isset($this->_tableClasses[$type])) {
            throw new \InvalidArgumentException('Invalid item type: ' . $type);
        }
        return $this->_serviceManager->get('Database\Table\\' . $this->_tableClasses[$type]);
    }

    /**
     * Get items with given property
     *
     * A standard filter "Client" is defined for all types, limiting results to
     * the given client ID. A plugin may define additional filters.
     *
     * @param string $type Item type
     * @param array $filters Filters. Default: no filters
     * @param string $order Property to sort by. "id" sorts by item ID. Default: item specific
     * @param string $direction One of asc|desc. Default: asc
     * @return \Zend\Db\ResultSet\AbstractResultSet
     */
    public function getItems($type, $filters=null, $order=null, $direction='asc')
    {
        $table = $this->getTable($type);
        $hydrator = $table->getHydrator();

        $columns = array_values($hydrator->getNamingStrategy()->getExtractorMap());

        if (is_null($order)) {
            $order = $this->_defaultOrder[$type];
        }
        if ($order != 'id') {
            $order = $hydrator->extractName($order);
        }

        $select = $table->getSql()->select();
        $select->columns($columns)
               ->order(array($order => $direction));

        if (isset($filters['Client'])) {
            $select->where(array('hardware_id' => $filters['Client']));
        }

        return $table->selectWith($select);
    }

    /**
     * Delete items for given client
     *
     * @param integer $clientId Client ID
     */
    public function deleteItems($clientId)
    {
        $where = array('hardware_id' => $clientId);
        foreach ($this->_tableClasses as $table) {
            $this->_serviceManager->get("Database\\Table\\$table")->delete($where);
        }
    }
}
