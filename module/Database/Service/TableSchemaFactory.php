<?php

/**
 * Factory for TableSchema
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

namespace Database\Service;

use Database\Connection;
use Database\Schema\Comparator;
use Database\Schema\TableSchema;
use Database\Table\CustomFieldConfig;
use Interop\Container\ContainerInterface;

/**
 * Factory for TableSchema
 *
 * @codeCoverageIgnore
 */
class TableSchemaFactory implements \Laminas\ServiceManager\Factory\FactoryInterface
{
    /** {@inheritdoc} */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $connection = $container->get(Connection::class);
        return new TableSchema(
            $container->get('Database\Nada'),
            $connection,
            new Comparator($connection->getSchemaManager()),
            $container->get(CustomFieldConfig::class)
        );
    }
}
