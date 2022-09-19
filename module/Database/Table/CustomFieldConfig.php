<?php

/**
 * "accountinfo_config" table
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

namespace Database\Table;

use Nada\Column\AbstractColumn as Column;

/**
 * "accountinfo_config" table
 */
class CustomFieldConfig extends \Database\AbstractTable
{
    /**
     * Internal identifier for text, integer and float columns
     **/
    const INTERNALTYPE_TEXT = 0;

    /**
     * Internal identifier for clob columns
     **/
    const INTERNALTYPE_TEXTAREA = 1;

    /**
     * Internal identifier for date columns
     **/
    const INTERNALTYPE_DATE = 6;

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function __construct(\Laminas\ServiceManager\ServiceLocatorInterface $serviceLocator)
    {
        $this->table = 'accountinfo_config';
        parent::__construct($serviceLocator);
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    protected function postSetSchema($logger, $schema, $database, $prune)
    {
        // If table is empty, create default entries
        $logger->debug('Checking for existing custom field config.');
        if ($this->select()->count() == 0) {
            $this->insert(
                array(
                    'name' => 'TAG',
                    'type' => 0,
                    'account_type' => 'COMPUTERS',
                    'show_order' => 1,
                )
            );
            $this->insert(
                array(
                    'name' => 'TAG',
                    'type' => 0,
                    'account_type' => 'SNMP',
                    'show_order' => 1,
                )
            );
            $logger->info(
                'Default custom field config created.'
            );
        }
    }

    /**
     * Get field definitions
     *
     * @return array[] Array with field names as keys. Values are arrays with 'column' and 'type' elements.
     */
    public function getFields()
    {
        $columns = $this->_serviceLocator->get('Database\Nada')->getTable('accountinfo')->getColumns();
        $select = $this->getSql()->select();
        $select->columns(array('id', 'type', 'name'))
               ->where(array('account_type' => 'COMPUTERS'))
               ->order('show_order');
        // Determine name and type of each field. Silently ignore unsupported field types.
        $fields = array();
        foreach ($this->selectWith($select) as $field) {
            $name = $field['name'];
            if ($name == 'TAG') {
                $column = 'tag';
                $type = 'text';
            } else {
                $column = $columns['fields_' . $field['id']];
                $type = null;
                switch ($field['type']) {
                    case self::INTERNALTYPE_TEXT:
                        // Can be text, integer or float. Evaluate column datatype.
                        switch ($column->getDatatype()) {
                            case Column::TYPE_VARCHAR:
                                $type = 'text';
                                break;
                            case Column::TYPE_INTEGER:
                                $type = 'integer';
                                break;
                            case Column::TYPE_FLOAT:
                                $type = 'float';
                                break;
                        }
                        break;
                    case self::INTERNALTYPE_TEXTAREA:
                        $type = 'clob';
                        break;
                    case self::INTERNALTYPE_DATE:
                        // ocsreports creates date columns as varchar(10)
                        // and stores values in a non-ISO format. Silently
                        // ignore these fields. Only accept real date
                        // columns.
                        if ($column->getDatatype() == Column::TYPE_DATE) {
                            $type = 'date';
                        }
                        break;
                }
                $column = $column->getName();
            }
            if ($type) {
                $fields[$name]['column'] = $column;
                $fields[$name]['type'] = $type;
            }
        }
        return $fields;
    }


    /**
     * Add field
     *
     * @param string $name Field name
     * @param string $type One of text, clob, integer, float or date
     * @throws \InvalidArgumentException if $type is not a valid field type
     * @internal
     */
    public function addField($name, $type)
    {
        $length = null;
        switch ($type) {
            case 'text':
                $datatype = Column::TYPE_VARCHAR;
                $length = 255;
                $internalType = self::INTERNALTYPE_TEXT;
                break;
            case 'integer':
                $datatype = Column::TYPE_INTEGER;
                $internalType = self::INTERNALTYPE_TEXT;
                break;
            case 'float':
                $datatype = Column::TYPE_FLOAT;
                $internalType = self::INTERNALTYPE_TEXT;
                break;
            case 'date':
                $datatype = Column::TYPE_DATE;
                $internalType = self::INTERNALTYPE_DATE;
                break;
            case 'clob':
                $datatype = Column::TYPE_CLOB;
                $internalType = self::INTERNALTYPE_TEXTAREA;
                break;
            default:
                throw new \InvalidArgumentException('Invalid datatype: ' . $type);
        }

        $connection = $this->adapter->getDriver()->getConnection();
        $nada = $this->_serviceLocator->get('Database\Nada');

        $connection->beginTransaction();

        try {
            $select = $this->getSql()->select();
            $select->columns(array('show_order' => new \Laminas\Db\Sql\Literal('MAX(show_order) + 1')))
                ->where(array('account_type' => 'COMPUTERS'));
            $order = $this->selectWith($select)->current()['show_order'];

            $this->insert(
                array(
                    'type' => $internalType,
                    'name' => $name,
                    'show_order' => $order,
                    'account_type' => 'COMPUTERS'
                )
            );
            $select = $this->getSql()->select();
            $select->columns(array('id'))->where(array('account_type' => 'COMPUTERS', 'name' => $name));
            $id = $this->selectWith($select)->current()['id'];

            $nada->getTable('accountinfo')->addColumn("fields_$id", $datatype, $length);

            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollback();
            throw $e;
        }
    }

    /**
     * Rename field
     *
     * @param string $oldName Existing field name
     * @param string $newName New field name
     * @internal
     **/
    public function renameField($oldName, $newName)
    {
        $this->update(
            array('name' => $newName),
            array(
                'name' => $oldName,
                'account_type' => 'COMPUTERS'
            )
        );
    }

    /**
     * Delete a field definition and all its values
     *
     * @param string $name Field name
     * @internal
     **/
    public function deleteField($name)
    {
        $connection = $this->adapter->getDriver()->getConnection();
        $connection->beginTransaction();

        try {
            $select = $this->getSql()->select();
            $select->columns(array('id'))->where(array('name' => $name, 'account_type' => 'COMPUTERS'));
            $id = $this->selectWith($select)->current()['id'];

            $this->delete(array('id' => $id));
            $this->_serviceLocator->get('Database\Nada')->getTable('accountinfo')->dropColumn('fields_' . $id);

            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollback();
            throw $e;
        }
    }
}
