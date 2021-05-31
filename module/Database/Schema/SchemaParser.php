<?php

/**
 * Database schema parser
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

namespace Database\Schema;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use InvalidArgumentException;

/**
 * Database schema parser
 */
class SchemaParser
{
    /**
     * @var AbstractPlatform
     */
    protected $platform;

    public function __construct(AbstractPlatform $platform)
    {
        $this->platform = $platform;
    }

    /**
     * Create Table object from schema.
     */
    public function parseTable(array $schema): Table
    {
        $columns = [];
        foreach ($schema['columns'] as $column) {
            $columns[] = $this->parseColumn($column);
        }

        $table = new Table($schema['name'], $columns);
        $table->setComment($schema['comment']);

        if (isset($schema['indexes'])) {
            foreach ($schema['indexes'] as $index) {
                $table->addIndex($index['columns'], $index['name']);
            }
        }

        if (isset($schema['unique_constraints'])) {
            foreach ($schema['unique_constraints'] as $uniqueConstraint) {
                $table->addUniqueConstraint($uniqueConstraint['columns'], $uniqueConstraint['name']);
            }
        }

        if (isset($schema['foreign_keys'])) {
            foreach ($schema['foreign_keys'] as $foreignKey) {
                $options = [];
                if (isset($foreignKey['on_delete'])) {
                    $options['onDelete'] = strtoupper($foreignKey['on_delete']);
                }
                $table->addForeignKeyConstraint(
                    $foreignKey['foreign_table'],
                    $foreignKey['local_columns'],
                    $foreignKey['foreign_columns'],
                    $options,
                    $foreignKey['name'] ?? null
                );
            }
        }

        if ($this->platform->getName() == 'mysql') {
            $table->addOption('engine', $schema['mysql']['engine']);
            $table->addOption('charset', 'utf8mb4');
            $defaultPrimaryKeyName = 'PRIMARY';
        } else {
            $defaultPrimaryKeyName = $schema['name'] . '_pkey';
        }

        // Actual PK name of existing tables may be different from default. The
        // schema management code uses the real name.
        $table->setPrimaryKey($schema['primary_key'], $defaultPrimaryKeyName);

        return $table;
    }

    /**
     * Create Column object from schema.
     */
    public function parseColumn(array $column): Column
    {
        $options = [
            'Notnull' => $column['notnull'],
            'Default' => $column['default'],
            'Autoincrement' => $column['autoincrement'],
            'Comment' => $column['comment'],
        ];
        switch ($column['type']) {
            case 'integer':
                switch ($column['length']) {
                    case 16:
                        $type = Type::getType(Types::SMALLINT);
                        break;
                    case 32:
                        $type = Type::getType(Types::INTEGER);
                        break;
                    case 64:
                        $type = Type::getType(Types::BIGINT);
                        break;
                    default:
                        throw new InvalidArgumentException('Invalid integer column length: ' . $column['length']);
                }
                break;
            case 'varchar':
                $type = Type::getType(Types::STRING);
                $options['Length'] = $column['length'];
                break;
            case 'bool':
                $type = Type::getType(Types::BOOLEAN);
                break;
            case 'timestamp':
                $type = Type::getType(Types::DATETIME_MUTABLE);
                break;
            case 'date':
                $type = Type::getType(Types::DATE_MUTABLE);
                break;
            case 'decimal':
                $type = Type::getType(Types::DECIMAL);
                [$options['Precision'], $options['Scale']] = explode(',', $column['length']);
                break;
            case 'clob':
                $type = Type::getType(Types::TEXT);
                break;
            case 'blob':
                $type = Type::getType(Types::BLOB);
                break;
            default:
                throw new InvalidArgumentException('Invalid column type: ' . $column['type']);
        }

        return new Column($column['name'], $type, $options);
    }
}
