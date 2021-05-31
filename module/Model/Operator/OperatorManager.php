<?php

/**
 * Operator manager
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

namespace Model\Operator;

use Database\Connection;
use Database\Table\Operators;
use Iterator;
use RuntimeException;

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
     * @var Connection
     */
    protected $connection;

    public function __construct(
        AuthenticationService $authenticationService,
        Operators $operators,
        Connection $connection
    ) {
        $this->_authenticationService = $authenticationService;
        $this->_operators = $operators;
        $this->connection = $connection;
    }

    /**
     * Fetch all operators
     */
    public function getOperators($order = 'Id', $direction = 'asc'): Iterator
    {
        $query = $this->connection->createQueryBuilder();
        $query->select(['id', 'firstname', 'lastname', 'email', 'comments'])->from(Operators::TABLE);

        $order = $this->_operators->getHydrator()->extractName($order);
        if ($order) {
            $query->orderBy($order, $direction);
        }

        return $this->_operators->getIterator($query->execute()->iterateAssociative());
    }

    /**
     * Get all login IDs
     *
     * @return string[]
     */
    public function getAllIds(): array
    {
        $query = $this->connection->createQueryBuilder();
        $query->select('id')->from(Operators::TABLE);

        return $query->execute()->fetchFirstColumn();
    }

    /**
     * Get operator with given ID
     *
     * @param string $id
     * @throws \InvalidArgumentException if no ID is given.
     * @throws \RuntimeException if no account with given name exists
     */
    public function getOperator($id): Operator
    {
        if (!is_string($id) or $id == '') {
            throw new \InvalidArgumentException('No login name supplied');
        }

        $query = $this->connection->createQueryBuilder();
        $query->select(['id', 'firstname', 'lastname', 'email', 'comments'])
              ->from(Operators::TABLE)
              ->where('id = ?');
        $operator = $query->setParameters([$id])->execute()->fetchAssociative();
        if (!$operator) {
            throw new \RuntimeException('Invalid login name supplied');
        }

        return $this->_operators->getHydrator()->hydrate($operator, $this->_operators->getPrototype());
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
        $insert = @$this->_operators->getHydrator()->extract(new \ArrayObject($data));
        unset($insert['']); // caused by unrecognized key, ignore
        $insert['passwd'] = $this->_authenticationService->getAdapter()->generateHash($password);
        $insert['password_version'] = \Database\Table\Operators::HASH_DEFAULT;

        $this->connection->insert(Operators::TABLE, $insert);
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
        $update = @$this->_operators->getHydrator()->extract(new \ArrayObject($data));
        unset($update['']); // caused by unrecognized key, ignore
        // Set password if specified
        if ($password) {
            $update['passwd'] = $this->_authenticationService->getAdapter()->generateHash($password);
            $update['password_version'] = \Database\Table\Operators::HASH_DEFAULT;
        }
        if (!$this->connection->update(Operators::TABLE, $update, ['id' => $id])) {
            throw new RuntimeException('Invalid user name: ' . $id);
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
        $this->connection->delete(Operators::TABLE, ['id' => $id]);
    }
}
