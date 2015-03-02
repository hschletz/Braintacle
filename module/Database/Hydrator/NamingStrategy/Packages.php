<?php
/**
 * Naming strategy for Packages table
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

namespace Database\Hydrator\NamingStrategy;

/**
 * Naming strategy for Packages table
 */
class Packages extends AbstractMappingStrategy
{
    /** {@inheritdoc} */
    protected $_hydratorMap = array(
        'name' => 'Name',
        'fileid' => 'Id',
        'priority' => 'Priority',
        'fragments' => 'NumFragments',
        'size' => 'Size',
        'osname' => 'Platform',
        'comment' => 'Comment',
        'num_nonnotified' => 'NumNonnotified',
        'num_success' => 'NumSuccess',
        'num_notified' => 'NumNotified',
        'num_error' => 'NumError',
    );

    /** {@inheritdoc} */
    protected $_extractorMap = array(
        'Name' => 'name',
        'Id' => 'fileid',
        'Priority' => 'priority',
        'NumFragments' => 'fragments',
        'Size' => 'size',
        'Platform' => 'osname',
        'Comment' => 'comment',
        'Timestamp' => 'fileid',
        'NumNonnotified' => 'num_nonnotified',
        'NumSuccess' => 'num_success',
        'NumNotified' => 'num_notified',
        'NumError' => 'num_error',
    );
}
