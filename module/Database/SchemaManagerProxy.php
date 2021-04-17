<?php

/**
 * Doctrine schema manager extension
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

namespace Database;

use Doctrine\DBAL\Schema\AbstractSchemaManager;

/**
 * Doctrine schema manager extension
 */
class SchemaManagerProxy
{
    /**
     * @var AbstractSchemaManager
     */
    protected $schemaManager;

    public function __construct(AbstractSchemaManager $schemaManager)
    {
        $this->schemaManager = $schemaManager;
    }

    /**
     * Proxy all unimplemented methods to underlying schema manager instance.
     */
    public function __call($name, $arguments)
    {
        return $this->schemaManager->$name(...$arguments);
    }

    /**
     * Query for existing view.
     */
    public function hasView(string $name): bool
    {
        $platform = $this->schemaManager->getDatabasePlatform();
        if ($platform->supportsSchemas()) {
            $name = $platform->getDefaultSchemaName() . '.' . $name;
        }

        return isset($this->schemaManager->listViews()[$name]);
    }
}
