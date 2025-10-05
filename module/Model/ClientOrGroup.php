<?php

/**
 * Base class for clients and groups
 *
 * Copyright (C) 2011-2025 Holger Schletz <holger.schletz@web.de>
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

namespace Model;

use Psr\Container\ContainerInterface;

/**
 * Base class for clients and groups
 *
 * Clients and groups share some common functionality. They can have packages
 * assigned, support individual configuration and concurrent writes are
 * controlled via a locking mechanism.
 * Since there is no database-level distinction between clients and groups for
 * the implementation of this functionality, this class implements the common
 * functionality for both objects.
 */
abstract class ClientOrGroup extends AbstractModel
{
    /**
     * @internal
     * Scan value in 'devices' table
     */
    const SCAN_DISABLED = 0;

    /**
     * @internal
     * Scan value in 'devices' table
     */
    const SCAN_EXPLICIT = 2;

    protected ContainerInterface $container;

    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
    }
}
