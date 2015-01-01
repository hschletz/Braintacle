<?php
/**
 * Operator manager
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

namespace Model\Operator;

/**
 * Operator manager
 */
class OperatorManager
{
    /**
     * Authentication service
     * @var \Library\Authentication\AuthenticationService
     */
    protected $_authenticationService;

    /**
     * Operators table
     * @var \Database\Table\Operators
     */
    protected $_operators;

    /**
     * Constructor
     *
     * @param \Library\Authentication\AuthenticationService $authenticationService
     * @param \Database\Table\Operators $operators
     */
    public function __construct(
        \Library\Authentication\AuthenticationService $authenticationService,
        \Database\Table\Operators $operators
    )
    {
        $this->_authenticationService = $authenticationService;
        $this->_operators = $operators;
    }

    /**
     * Fetch all operators
     *
     * @param string $order Property to sort by
     * @param string $direction Sorting order (asc|desc)
     * @return \Model_Account[]
     */
    public function fetchAll($order='Id', $direction='asc')
    {
        $operator = \Library\Application::getService('Model\Operator\Operator');
        $select = $this->_operators->getSql()->select();
        $select->columns(array('id', 'firstname', 'lastname', 'email', 'comments'))
               ->order(\Model_Account::getOrder($order, $direction, $operator->getPropertyMap()));
        return $this->_operators->selectWith($select);
    }

    /**
     * Get all login IDs
     *
     * @return string[]
     */
    public function getAllIds()
    {
        return $this->_operators->fetchCol('id');
    }

    /**
     * Get operator with given ID
     *
     * @param string $id
     * @throws \InvalidArgumentException if no ID is given.
     * @throws \RuntimeException if no account with given name exists
     */
    public function get($id)
    {
        if (!is_string($id) or $id == '') {
            throw new \InvalidArgumentException('No login name supplied');
        }
        $select = $this->_operators->getSql()->select();
        $select->columns(array('id', 'firstname', 'lastname', 'email', 'comments'))
               ->where(array('id' => $id));
        $operator = $this->_operators->selectWith($select)->current();
        if (!$operator) {
            throw new \RuntimeException('Invalid login name supplied');
        }
        return $operator;
    }

    /**
     * Create new operator account
     *
     * @param array $data List of properties to set. Unknown keys will be ignored.
     * @param string $password Password for the new account, must not be empty.
     * @throws \InvalidArgumentException if no ID or password is given.
     */
    public function create($data, $password)
    {
        if (!@$data['Id']) {
            throw new \InvalidArgumentException('No login name supplied');
        }
        if (!$password) {
            throw new \InvalidArgumentException('No password supplied');
        }

        $operator = \Library\Application::getService('Model\Operator\Operator');
        $propertyMap = $operator->getPropertyMap();
        // Compose array of columns to set
        foreach ($data as $property => $value) {
            if (isset($propertyMap[$property])) { // Ignore unknown keys
                $insert[$propertyMap[$property]] = $value;
            }
        }
        $insert['passwd'] = md5($password);
        $insert['accesslvl'] = \Model_Account::OLD_PRIVILEGE_ADMIN;
        $insert['new_accesslvl'] = \Model_Account::PRIVILEGE_ADMIN;

        $this->_operators->insert($insert);
    }

    /**
     * Delete operator account
     *
     * @param string $id Login name of account to delete
     * @throws \RuntimeException if the account to delete is logged in for the current session
     */
    public function delete($id)
    {
        if ($id == $this->_authenticationService->getIdentity()) {
            throw new \RuntimeException('Cannot delete account of current user');
        }
        $this->_operators->delete(array('id' => $id));
    }
}
