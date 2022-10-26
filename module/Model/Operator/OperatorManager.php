<?php

/**
 * Operator manager
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

namespace Model\Operator;

/**
 * Operator manager
 */
class OperatorManager
{
    /**
     * Authentication service
     * @var \Model\Operator\AuthenticationService
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
     * @param \Model\Operator\AuthenticationService $authenticationService
     * @param \Database\Table\Operators $operators
     */
    public function __construct(
        \Model\Operator\AuthenticationService $authenticationService,
        \Database\Table\Operators $operators
    ) {
        $this->_authenticationService = $authenticationService;
        $this->_operators = $operators;
    }

    /**
     * Fetch all operators
     *
     * @param string $order Property to sort by
     * @param string $direction Sorting order (asc|desc)
     * @return \Laminas\Db\ResultSet\AbstractResultSet Result set producing \Model\Operator\Operator
     */
    public function getOperators($order = 'Id', $direction = 'asc')
    {
        $select = $this->_operators->getSql()->select();
        $select->columns(array('id', 'firstname', 'lastname', 'email', 'comments'));

        $order = $this->_operators->getHydrator()->extractName($order);
        if ($order) {
            $select->order(array($order => $direction));
        }
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
    public function getOperator($id)
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
    public function createOperator($data, $password)
    {
        if (!@$data['Id']) {
            throw new \InvalidArgumentException('No login name supplied');
        }
        if (!$password) {
            throw new \InvalidArgumentException('No password supplied');
        }

        // Compose array of columns to set
        $insert = @$this->_operators->getHydrator()->extract(new Operator($data));
        unset($insert['']); // caused by unrecognized key, ignore
        $insert['passwd'] = $this->_authenticationService->getAdapter()->generateHash($password);
        $insert['password_version'] = \Database\Table\Operators::HASH_DEFAULT;

        $this->_operators->insert($insert);
    }

    /**
     * Update existing operator account
     *
     * @param string $id Login name of account to update
     * @param string[] $data List of properties to set. Unknown keys will be ignored.
     * @param string $password New password. If empty, password will remain unchanged.
     */
    public function updateOperator($id, $data, $password)
    {
        // Compose array of columns to set
        $update = @$this->_operators->getHydrator()->extract(new Operator($data));
        unset($update['']); // caused by unrecognized key, ignore
        // Set password if specified
        if ($password) {
            $update['passwd'] = $this->_authenticationService->getAdapter()->generateHash($password);
            $update['password_version'] = \Database\Table\Operators::HASH_DEFAULT;
        }
        if (!$this->_operators->update($update, array('id' => $id))) {
            throw new \RuntimeException('Invalid user name: ' . $id);
        }
        if (isset($data['Id']) and $id == $this->_authenticationService->getIdentity()) {
            // If the account name of the logged in user is changed, the
            // identity must be updated to remain valid.
            $this->_authenticationService->changeIdentity($data['Id']);
        }
    }

    /**
     * Delete operator account
     *
     * @param string $id Login name of account to delete
     * @throws \RuntimeException if the account to delete is logged in for the current session
     */
    public function deleteOperator($id)
    {
        if ($id == $this->_authenticationService->getIdentity()) {
            throw new \RuntimeException('Cannot delete account of current user');
        }
        $this->_operators->delete(array('id' => $id));
    }
}
