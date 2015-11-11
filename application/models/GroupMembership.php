<?php
/**
 * Class representing a group membership
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
 *
 * @package Models
 */
/**
 * Group membership
 *
 * Properties:
 *
 * - <b>GroupId</b> Group ID
 * - <b>GroupName</b> Group name
 * - <b>Membership</b> Membership type - one of {@link TYPE_DYNAMIC},
 * {@link TYPE_STATIC} or {@link TYPE_EXCLUDED}.
 *
 *
 * Always use the class constants for membership types instead of plain
 * integers, and treat them as enum-style values, i.e. don't perform
 * comparisions other than '==' or '===' on them. The actual range of values is
 * handled internally and might change in the future.
 * @package Models
 */
class Model_GroupMembership extends \ArrayObject
{

    // Class constants describing membership types.
    // The first three map to the value of groups_cache.static
    const TYPE_DYNAMIC = 0;
    const TYPE_STATIC = 1;
    const TYPE_EXCLUDED = 2;
    // The next one is not present in groups_cache.static.
    // It refers to both statically included or excluded.
    const TYPE_MANUAL = -2;
    // The next one is not present in groups_cache.static.
    // It refers to all membership types.
    const TYPE_ALL = -3;
}
