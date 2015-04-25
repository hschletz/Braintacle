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
        'Display' => 'Displays',
        'ExtensionSlot' => 'ExtensionSlots',
        'InputDevice' => 'InputDevices',
        'Modem' => 'Modems',
        'Port' => 'Ports',
        'Printer' => 'Printers',
    );

    /**
     * Plugins for specific types (if DefaultPlugin is not sufficient)
     * @var string[]
     */
    protected $_plugins = array(
        'ExtensionSlot' => 'ExtensionSlot',
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
     * @param string $type Item type
     * @param array $filters Filters, handled by plugin. Default: no filters
     * @param string $order Property to sort by, handled by plugin.
     * @param string $direction One of asc|desc. Default: asc
     * @return \Zend\Db\ResultSet\AbstractResultSet
     */
    public function getItems($type, $filters=null, $order=null, $direction='asc')
    {
        $table = $this->getTable($type);

        if (isset($this->_plugins[$type])) {
            $pluginClass = 'Model\Client\Plugin\\' . $this->_plugins[$type];
        } else {
            $pluginClass = 'Model\Client\Plugin\DefaultPlugin';
        }
        $plugin = new $pluginClass($table);
        $plugin->columns();
        $plugin->join();
        $plugin->where($filters);
        $plugin->order($order, $direction);

        return $table->selectWith($plugin->select());
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
