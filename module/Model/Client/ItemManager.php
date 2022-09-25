<?php

/**
 * Client item manager
 *
 * Copyright (C) 2011-2022 Holger Schletz <holger.schletz@web.de>
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

use Laminas\Db\ResultSet\AbstractResultSet;

/**
 * Client item manager
 *
 * Item types are named by their corresponding model class (without prefix, case
 * insensitive).
 */
class ItemManager
{
    /**
     * Map of item types to table classes
     * @var string[]
     */
    protected $_tableClasses = array(
        'audiodevice' => 'AudioDevices',
        'controller' => 'Controllers',
        'cpu' => 'Cpu',
        'display' => 'Displays',
        'displaycontroller' => 'DisplayControllers',
        'extensionslot' => 'ExtensionSlots',
        'filesystem' => 'Filesystems',
        'inputdevice' => 'InputDevices',
        'memoryslot' => 'MemorySlots',
        'modem' => 'Modems',
        'msofficeproduct' => 'MsOfficeProducts',
        'networkinterface' => 'NetworkInterfaces',
        'port' => 'Ports',
        'printer' => 'Printers',
        'registrydata' => 'RegistryData',
        'sim' => 'Sim',
        'software' => 'Software',
        'storagedevice' => 'StorageDevices',
        'virtualmachine' => 'VirtualMachines',
    );

    /**
     * Plugins for specific types (if DefaultPlugin is not sufficient)
     * @var string[]
     */
    protected $_plugins = array(
        'controller' => 'Controller',
        'cpu' => 'Cpu',
        'extensionslot' => 'ExtensionSlot',
        'filesystem' => 'Filesystem',
        'msofficeproduct' => 'MsOfficeProduct',
        'networkinterface' => 'NetworkInterface',
        'registrydata' => 'RegistryData',
        'software' => 'Software',
        'storagedevice' => 'StorageDevice',
    );

    /**
     * Service manager
     * @var \Laminas\ServiceManager\ServiceManager
     */
    protected $_serviceManager;

    /**
     * Constructor
     *
     * @param \Laminas\ServiceManager\ServiceManager $serviceManager
     */
    public function __construct(\Laminas\ServiceManager\ServiceManager $serviceManager)
    {
        $this->_serviceManager = $serviceManager;
    }

    /**
     * List all valid item types
     *
     * @return string[]
     */
    public function getItemTypes()
    {
        return array_keys($this->_tableClasses);
    }

    /**
     * Get table name for given type
     *
     * @param string $type Item type
     * @return string Table name without namespace prefix
     * @throws \InvalidArgumentException if $type is not defined
     */
    public function getTableName($type)
    {
        $key = strtolower($type);
        if (!isset($this->_tableClasses[$key])) {
            throw new \InvalidArgumentException('Invalid item type: ' . $type);
        }
        return $this->_tableClasses[$key];
    }

    /**
     * Get table gateway for given type
     *
     * @param string $type Item type
     * @return \Database\AbstractTable
     */
    public function getTable($type)
    {
        return $this->_serviceManager->get('Database\Table\\' . $this->getTableName($type));
    }

    /**
     * Get items with given property
     *
     * @param string $type Item type
     * @param array $filters Filters, handled by plugin. Default: no filters
     * @param string $order Property to sort by, handled by plugin.
     * @param string $direction One of asc|desc. Default: asc
     */
    public function getItems(
        string $type,
        array $filters = null,
        string $order = null,
        ?string $direction = 'asc'
    ): AbstractResultSet {
        $type = strtolower($type);
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
        $plugin->order($order, $direction ?? 'asc');

        return $plugin->select();
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
