<?php

/**
 * Default item plugin
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

namespace Model\Client\Plugin;

/**
 * Default item plugin
 */
class DefaultPlugin
{
    /**
     * Table gateway
     * @var \Database\AbstractTable
     */
    protected $_table;

    /**
     * Select object for query
     * @var \Laminas\Db\Sql\Select
     */
    protected $_select;

    /**
     * Default properties to sort by
     * @var string[]
     */
    protected $_defaultOrder = array(
        'AudioDevices' => 'Manufacturer',
        'Cpu' => 'Type',
        'Displays' => 'Manufacturer',
        'DisplayControllers' => 'Name',
        'InputDevices' => 'Type',
        'Modems' => 'Type',
        'MemorySlots' => 'SlotNumber',
        'MsOfficeProducts' => 'Name',
        'NetworkInterfaces' => 'Description',
        'Ports' => 'Name',
        'Printers' => 'Name',
        'RegistryData' => 'Value',
        'Sim' => 'OperatorName',
        'Software' => 'Name',
        'VirtualMachines' => 'Name',
    );

    /**
     * Constructor
     *
     * @param \Database\AbstractTable $table Base table
     */
    public function __construct(\Database\AbstractTable $table)
    {
        $this->_table = $table;
        $this->_select = $table->getSql()->select();
    }

    /**
     * Retrieve items from database
     *
     * @return \Laminas\Db\ResultSet\AbstractResultSet
     */
    public function select()
    {
        return $this->_table->selectWith($this->_select);
    }

    /**
     * Set columns
     *
     * Default implementation: Query the table's hydrator for available columns
     */
    public function columns()
    {
        $columns = array_values($this->_table->getHydrator()->getNamingStrategy()->getExtractionMap());
        $this->_select->columns($columns);
    }

    /**
     * Join tables
     *
     * Default implementation: none
     */
    public function join()
    {
    }

    /**
     * Filter results
     *
     * Default implementation: Provide a "Client" filter to limit results to
     * client with given ID.
     *
     * @param array $filters Filter specifications
     */
    public function where($filters)
    {
        if (isset($filters['Client'])) {
            $this->_select->where(array('hardware_id' => $filters['Client']));
        }
    }

    /**
     * Sort results
     *
     * Default implementation:
     * - NULL: type specific default
     * - "id": item ID
     * - other: query table's hydrator for matching column name
     */
    public function order(?string $order, string $direction): void
    {
        if (is_null($order)) {
            $tableClass = get_class($this->_table);
            $order = $this->_defaultOrder[substr($tableClass, strrpos($tableClass, '\\') + 1)];
        }
        if ($order != 'id') {
            $order = $this->_table->getHydrator()->extractName($order);
        }
        $this->_select->order(array($this->_table->table . ".$order" => $direction));
    }
}
