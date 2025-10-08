<?php

/**
 * A group of clients
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

namespace Model\Group;

use DateTimeInterface;

/**
 * A group of clients
 *
 * Packages and settings assigned to a group apply to all members. Clients can
 * become a member by manual assignment or automatically based on the result of
 * a query. It is also possible to unconditionally exclude a client from a group
 * regardless of query result.
 *
 * @psalm-suppress PossiblyUnusedProperty -- referenced in template
 */
class Group
{
    /**
     * Primary key
     */
    public int $id;

    /**
     * Name
     */
    public string $name;

    /**
     * Description
     */
    public ?string $description;

    /**
     * Timestamp of group creation
     */
    public DateTimeInterface $creationDate;

    /**
     * SQL query for dynamic members, may be empty
     */
    public ?string $dynamicMembersSql;

    /**
     * Timestamp of last cache update
     */
    public ?DateTimeInterface $cacheCreationDate;

    /**
     * Timestamp when cache will expire and get rebuilt
     */
    public ?DateTimeInterface $cacheExpirationDate;
}
