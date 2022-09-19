<?php

/**
 * Registry manager
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

namespace Model\Registry;

/**
 * Registry manager
 */
class RegistryManager
{
    /**
     * RegistryValueDefinitions table
     * @var \Database\Table\RegistryValueDefinitions
     */
    protected $_registryValueDefinitions;

    /**
     * RegistryData table
     * @var \Database\Table\RegistryData
     */
    protected $_registryData;

    /**
     * Constructor
     *
     * @param \Database\Table\RegistryValueDefinitions $registryValueDefinitions
     * @param \Database\Table\RegistryData $registryData
     */
    public function __construct(
        \Database\Table\RegistryValueDefinitions $registryValueDefinitions,
        \Database\Table\RegistryData $registryData
    ) {
        $this->_registryValueDefinitions = $registryValueDefinitions;
        $this->_registryData = $registryData;
    }

    /**
     * Get all registry value definitions
     *
     * @return \Laminas\Db\ResultSet\AbstractResultSet Result set producing \Model\Registry\Value, sorted by name
     */
    public function getValueDefinitions()
    {
        $select = $this->_registryValueDefinitions->getSql()->select();
        $select->columns(array('id', 'name', 'regtree', 'regkey', 'regvalue'));
        $select->order('name');
        return $this->_registryValueDefinitions->selectWith($select);
    }

    /**
     * Get registry value definition with given name
     *
     * @param string $name Name of an existing value definition
     * @return \Model\Registry\Value
     * @throws \RuntimeException if given name is invalid
     **/
    public function getValueDefinition($name)
    {
        $select = $this->_registryValueDefinitions->getSql()->select();
        $select->columns(array('id', 'name', 'regtree', 'regkey', 'regvalue'));
        $select->where(array('name' => $name));
        $value = $this->_registryValueDefinitions->selectWith($select)->current();
        if (!$value) {
            throw new RuntimeException('Invalid registry value name: ' . $name);
        }
        return $value;
    }

    /**
     * Add a value definition
     *
     * @param string $name Name of new value
     * @param integer $rootKey One of the HKEY_* constants from \Model\Registry\Value
     * @param string $subKeys Path to the key that contains the value, with components separated by backslashes
     * @param string $value Inventory only given value (default: all values for the given key)
     * @throws \InvalidArgumentException if $subKeys is empty
     * @throws \DomainException if $rootkey is not one of the HKEY_* constants
     * @throws \Model\Registry\RuntimeException if a value with the same name already exists.
     **/
    public function addValueDefinition($name, $rootKey, $subKeys, $value = null)
    {
        if (empty($subKeys)) {
            throw new \InvalidArgumentException('Subkeys must not be empty');
        }
        if (!isset(\Model\Registry\Value::rootKeys()[$rootKey])) {
            throw new \DomainException('Invalid root key: ' . $rootKey);
        }
        if ($this->_registryValueDefinitions->select(array('name' => $name))->count()) {
            throw new RuntimeException('Value already exists: ' . $name);
        }

        if (!$value) {
            $value = '*';
        }
        $this->_registryValueDefinitions->insert(
            array(
                'name' => $name,
                'regtree' => $rootKey,
                'regkey' => $subKeys,
                'regvalue' => $value
            )
        );
    }

    /**
     * Rename a value definition
     *
     * @param string $oldName Existing name
     * @param string $newName New name. If identical with existing name, do nothing.
     * @throws \InvalidArgumentException if $name is empty
     * @throws \RuntimeException if a definition with the same name already exists.
     **/
    public function renameValueDefinition($oldName, $newName)
    {
        if ($newName == $oldName) {
            return;
        }
        if ($newName == '') {
            throw new \InvalidArgumentException('Name must not be empty');
        }
        if ($this->_registryValueDefinitions->select(array('name' => $newName))->count()) {
            throw new \RuntimeException('Value already exists: ' . $newName);
        }

        $connection = $this->_registryValueDefinitions->getAdapter()->getDriver()->getConnection();
        $connection->beginTransaction();
        try {
            $this->_registryData->update(
                array('name' => $newName),
                array('name' => $oldName)
            );
            $this->_registryValueDefinitions->update(
                array('name' => $newName),
                array('name' => $oldName)
            );
            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollback();
            throw $e;
        }
    }

    /**
     * Delete a value definition and its inventoried data
     *
     * @param string $name Name of value definition. Nonexistent name is ignored.
     **/
    public function deleteValueDefinition($name)
    {
        $connection = $this->_registryValueDefinitions->getAdapter()->getDriver()->getConnection();
        $connection->beginTransaction();
        try {
            $this->_registryData->delete(['name' => $name]);
            $this->_registryValueDefinitions->delete(['name' => $name]);
            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollback();
            throw $e;
        }
    }
}
