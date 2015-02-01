<?php
/**
 * Class for managing custom fields
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
 * Class for managing custom fields
 */
class CustomFieldManager
{
    /**
     * CustomFieldConfig table
     * @var \Database\Table\CustomFieldConfig
     */
    protected $_customFieldConfig;

    /**
     * Map of field names => field types
     * @var string[]
     */
    private $_fields;

    /**
     * Map of field names => column names
     * @var string[]
     */
    private $_columnMap;

    /**
     * Constructor
     *
     * @param \Database\Table\CustomFieldConfig $customFieldConfig
     */
    public function __construct(\Database\Table\CustomFieldConfig $customFieldConfig)
    {
        $this->_customFieldConfig = $customFieldConfig;
    }

    /**
     * Populate field caches
     */
    private function _getFieldInfo()
    {
        $this->_fields = array();
        $this->_columnMap = array();
        foreach ($this->_customFieldConfig->getFields() as $name => $info) {
            $this->_fields[$name] = $info['type'];
            $this->_columnMap[$name] = $info['column'];
        }
    }

    /**
     * Get fields
     *
     * @return array Array with field names as keys, field types as values
     */
    public function getFields()
    {
        if (!($this->_fields)) {
            $this->_getFieldInfo();
        }
        return $this->_fields;
    }

    /**
     * Get column names
     *
     * @return array Array with field names as keys, column names as values
     */
    public function getColumnMap()
    {
        if (!$this->_columnMap) {
            $this->_getFieldInfo();
        }
        return $this->_columnMap;
    }

    /**
     * Check for presence of a field with given name (case insensitive)
     *
     * @param string $name Field name to check
     * @return bool
     **/
    public function fieldExists($name)
    {
        return (bool) preg_grep(
            '/^' . preg_quote($name, '/') . '$/ui',
            array_keys($this->getFields())
        );
    }

    /**
     * Add field
     *
     * @param string $name Field name
     * @param string $type One of text, clob, integer, float or date
     * @throws \InvalidArgumentException if column exists or is a system column
     **/
    public function addField($name, $type)
    {
        if ($this->fieldExists($name)) {
            throw new \InvalidArgumentException("Column '$name' already exists");
        }
        $this->_customFieldConfig->addField($name, $type);
        $this->_fields[$name] = $type;
        // force re-read on next usage via getColumnMap() because we don't know
        // the column name
        $this->_columnMap = null;
    }

    /**
     * Rename field
     *
     * @param string $oldName Existing field name
     * @param string $newName New field name
     * @throws \InvalidArgumentException if column does not exist or is a system column or new name exists
     **/
    public function renameField($oldName, $newName)
    {
        if ($oldName == 'TAG') {
            throw new \InvalidArgumentException('System column "TAG" cannot be renamed.');
        }
        if ($newName == 'TAG') {
            throw new \InvalidArgumentException('Column cannot be renamed to reserved name "TAG".');
        }
        if (!$this->fieldExists($oldName)) {
            throw new \InvalidArgumentException("Unknown column: \"$oldName\"");
        }
        // The equality check is required to allow renaming a field by just
        // changing the case of one or more characters.
        if (!preg_match('/^' . preg_quote($oldName, '/') . '$/ui', $newName) and $this->fieldExists($newName)) {
            throw new \InvalidArgumentException("Column \"$newName\" already exists.");
        }
        if ($newName == $oldName) {
            return;
        }

        $this->_customFieldConfig->renameField($oldName, $newName);
        // Force re-read on next usage.
        $this->_fields = null;
        $this->_columnMap = null;
    }

    /**
     * Delete a field definition and all its values
     *
     * @param string $name Field name
     * @throws \InvalidArgumentException if column does not exist or is a system column
     **/
    public function deleteField($name)
    {
        if ($name == 'TAG') {
            throw new \InvalidArgumentException('Cannot delete system column "TAG".');
        }
        if (!$this->fieldExists($name)) {
            throw new \InvalidArgumentException("Unknown column: \"$name\"");
        }

        $this->_customFieldConfig->deleteField($name);
        unset($this->_fields[$name]);
        unset($this->_columnMap[$name]);
    }
}
