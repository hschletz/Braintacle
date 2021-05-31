<?php

/**
 * "accountinfo_config" table
 *
 * Copyright (C) 2011-2021 Holger Schletz <holger.schletz@web.de>
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

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\TableDiff;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use InvalidArgumentException;
use Throwable;

/**
 * "accountinfo_config" table
 */
class CustomFieldConfig extends \Database\AbstractTable
{
    const TABLE = 'accountinfo_config';

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
     * @codeCoverageIgnore
     */
    protected function postSetSchema(array $schema, bool $prune): void
    {
        // If table is empty, create default entries
        $logger = $this->connection->getLogger();
        $logger->debug('Checking for existing custom field config.');
        if ($this->connection->executeQuery('SELECT * FROM ' . static::TABLE)->fetchOne() == 0) {
            $this->connection->insert(static::TABLE, [
                'name' => 'TAG',
                'type' => 0,
                'account_type' => 'COMPUTERS',
                'show_order' => 1,
            ]);
            $this->connection->insert(static::TABLE, [
                'name' => 'TAG',
                'type' => 0,
                'account_type' => 'SNMP',
                'show_order' => 1,
            ]);
            $logger->info(
                'Default custom field config created.'
            );
        }
    }

    /**
     * Get names of columns in the target table for which entries are defined here.
     */
    public function getTargetColumnNames(): array
    {
        $columns = [];
        // Table may not exist yet when populating an empty database. In that
        // case, there are no target columns.
        if ($this->connection->getSchemaManager()->tablesExist([static::TABLE])) {
            $query = $this->connection->createQueryBuilder();
            $query->select('id')->from(static::TABLE)->where('name_accountinfo IS NULL');
            foreach ($query->execute()->iterateColumn() as $id) {
                $columns[] = "fields_$id";
            }
        }

        return $columns;
    }

    /**
     * Get field definitions
     *
     * @return array[] Array with field names as keys. Values are arrays with 'column' and 'type' elements.
     */
    public function getFields()
    {
        $columns = $this->connection->getSchemaManager()->listTableColumns(CustomFields::TABLE);
        $query = $this->connection->createQueryBuilder();
        $query->select('id', 'type', 'name')
              ->from(static::TABLE)
              ->where("account_type = 'COMPUTERS'")
              ->orderBy('show_order');
        // Determine name and type of each field. Silently ignore unsupported field types.
        $fields = [];
        foreach ($query->execute()->iterateAssociative() as $field) {
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
                        switch ($column->getType()->getName()) {
                            case Types::STRING:
                                $type = 'text';
                                break;
                            case Types::INTEGER:
                                $type = 'integer';
                                break;
                            case Types::FLOAT:
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
                        if ($column->getType()->getName() == Types::DATE_MUTABLE) {
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
                $datatype = Types::STRING;
                $length = 255;
                $internalType = self::INTERNALTYPE_TEXT;
                break;
            case 'integer':
                $datatype = Types::INTEGER;
                $internalType = self::INTERNALTYPE_TEXT;
                break;
            case 'float':
                $datatype = Types::FLOAT;
                $internalType = self::INTERNALTYPE_TEXT;
                break;
            case 'date':
                $datatype = Types::DATE_MUTABLE;
                $internalType = self::INTERNALTYPE_DATE;
                break;
            case 'clob':
                $datatype = Types::TEXT;
                $internalType = self::INTERNALTYPE_TEXTAREA;
                break;
            default:
                throw new InvalidArgumentException('Invalid datatype: ' . $type);
        }

        $this->connection->beginTransaction();
        try {
            $query = $this->connection->createQueryBuilder();
            $query->select('max(show_order) + 1')->from(static::TABLE)->where("account_type = 'COMPUTERS'");
            $order = $query->execute()->fetchOne();

            $this->connection->insert(static::TABLE, [
                'type' => $internalType,
                'name' => $name,
                'show_order' => $order,
                'account_type' => 'COMPUTERS',
            ]);
            $query->select('id')->from(static::TABLE)->where("account_type = 'COMPUTERS' AND name = ?");
            $id = $query->setParameters([$name])->execute()->fetchOne();
            $columnName = 'fields_' . $id;

            $column = new Column($columnName, Type::getType($datatype));
            if ($length) {
                $column->setLength($length);
            }
            $this->connection->getSchemaManager()->addColumn(CustomFields::TABLE, $column);

            $this->connection->commit();
        } catch (Throwable $t) {
            $this->connection->rollBack();
            throw $t;
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
        $this->connection->update(
            static::TABLE,
            ['name' => $newName],
            [
                'name' => $oldName,
                'account_type' => 'COMPUTERS',
            ]
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
        $this->connection->beginTransaction();
        try {
            $query = $this->connection->createQueryBuilder();
            $query->select('id')->from(static::TABLE)->where('name = ?', "account_type = 'COMPUTERS'");
            $id = $query->setParameters([$name])->execute()->fetchOne();
            $this->connection->delete(static::TABLE, ['id' => $id]);

            $columnName = 'fields_' . $id;
            $this->connection->getSchemaManager()->dropColumn(CustomFields::TABLE, $columnName);

            $this->connection->commit();
        } catch (Throwable $t) {
            $this->connection->rollBack();
            throw $t;
        }
    }
}
