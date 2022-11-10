<?php

/**
 * Package assignment on a client
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

namespace Model\Package;

use DateTimeInterface;

/**
 * Package assignment on a client
 */
class Assignment
{
    /**
     * Database-internal date format
     *
     * This format can be passed to \date() and related functions to create or
     * parse a date string that is used to store the package assignment date in
     * the database. It is similar to the format created by the server except that
     * the day is zero-padded instead of space-padded. Code that parses these
     * date strings should be prepared to handle both variants.
     *
     * The date is not timezone-aware and should be assumed to be local time.
     */
    const DATEFORMAT = 'D M d H:i:s Y';

    /**
     * Database value for status "pending"
     */
    const PENDING = null;

    /**
     * Database value for status "running"
     */
    const RUNNING = 'NOTIFIED';

    /**
     * Database value for status "success"
     */
    const SUCCESS = 'SUCCESS';

    /**
     * Prefix of database value for error status
     */
    const ERROR_PREFIX = 'ERR';

    /**
     * Package name
     */
    public string $packageName;

    /**
     * Status (PENDING/RUNNUNG/SUCCESS/ERR_*)
     */
    public ?string $status;

    /**
     * Timestamp of last status change
     */
    public DateTimeInterface $timestamp;
}
