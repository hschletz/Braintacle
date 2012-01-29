<?php
/**
 * Class representing a virtual machine hosted on a computer
 *
 * $Id$
 *
 * Copyright (C) 2011,2012 Holger Schletz <holger.schletz@web.de>
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
 * A virtual machine hosted on a computer
 *
 * Properties:
 * - <b>Name</b> Name
 * - <b>Status</b> Status at inventory time
 * - <b>Product</b> Virtualization product
 * - <b>Type</b> VM type (some types are supported by different products)
 * - <b>Uuid</b> UUID
 * - <b>NumCpus</b> Number of guest CPUs. Unreliable because some agents always report 1 for some products.
 * - <b>GuestMemory</b> Guest RAM in MB
 * @package Models
 */
class Model_VirtualMachine extends Model_ChildObject
{

    /** {@inheritdoc} */
    protected $_propertyMap = array(
        // Values from 'virtualmachines' table
        'Name' => 'name',
        'Status' => 'status',
        'Product' => 'subsystem',
        'Type' => 'vmtype',
        'Uuid' => 'uuid',
        'NumCpus' => 'vcpu',
        'GuestMemory' => 'memory',
    );

    /** {@inheritdoc} */
    protected $_types = array(
        'NumCpus' => 'integer',
        'GuestMemory' => 'integer',
    );

    /** {@inheritdoc} */
    protected $_tableName = 'virtualmachines';

    /** {@inheritdoc} */
    protected $_preferredOrder = 'Name';
}
