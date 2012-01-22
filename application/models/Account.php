<?php
/**
 * Class representing a Braintacle user account
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
 * Class representing a Braintacle user account
 *
 * This is the interface to Braintacle's user accounts.
 * @package Models
 */
class Model_Account extends Model_Abstract
{

    /** {@inheritdoc} */
    protected $_propertyMap = array(
        'Id' => 'id',
        'FirstName' => 'firstname',
        'LastName' => 'lastname',
        'MailAddress' => 'email',
        'Comment' => 'comments',
    );

    /** {@inheritdoc} */
    protected $_types = array(
        'Comment' => 'clob',
    );

    /**
     * Return a statement object with all accounts
     * @param string $order Property to sort by
     * @param string Sorting order (asc|desc)
     * @return Zend_Db_Statement
     */
    public static function createStatementStatic($order='Id', $direction='asc')
    {
        $dummy = new self;
        $map = $dummy->getPropertyMap();
        return Zend_Registry::get('db')
            ->select()
            ->from('operators', array_values($map))
            ->order(self::getOrder($order, $direction, $map))
            ->query();
    }

}
