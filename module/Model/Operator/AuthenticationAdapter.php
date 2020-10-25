<?php
/**
 * Authentication adapter
 *
 * Copyright (C) 2011-2020 Holger Schletz <holger.schletz@web.de>
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

use \Zend\Authentication\Result;

/**
 * Authentication adapter
 *
 * This adapter should be used for authentication against the application's
 * database. Hashes are automatically converted to a more secure hash after
 * successful authentication if necessary.
 */
class AuthenticationAdapter extends \Zend\Authentication\Adapter\AbstractAdapter
{
    /**
     * The bcrypt hashing method truncates passwords to 72 bytes.
     */
    const PASSWORD_MAX_BYTES = 72;

    /**
     * Operators table
     * @var \Database\Table\Operators
     */
    protected $_operators;

    /**
     * Constructor
     *
     * @param \Database\Table\Operators $operators
     */
    public function __construct(\Database\Table\Operators $operators)
    {
        $this->_operators = $operators;
    }

    /**
     * {@inheritdoc}
     */
    public function authenticate()
    {
        $identity = $this->getIdentity();
        $credential = $this->getCredential();
        try {
            $sql = $this->_operators->getSql();
            $select = $sql->select();
            $select->columns(array('passwd', 'password_version'))
                   ->where(array('id' => $identity));
            $operator = $sql->prepareStatementForSqlObject($select)->execute()->current();
            if ($operator) {
                switch ($operator['password_version']) {
                    case \Database\Table\Operators::HASH_DEFAULT:
                        if (password_verify($credential, $operator['passwd'])) {
                            $result = new Result(Result::SUCCESS, $identity);
                            if (password_needs_rehash($operator['passwd'], \PASSWORD_DEFAULT)) {
                                $this->_operators->update(
                                    array('passwd' => $this->generateHash($credential)),
                                    array('id' => $identity)
                                );
                            }
                        } else {
                            $result = new Result(Result::FAILURE_CREDENTIAL_INVALID, null);
                        }
                        break;
                    case \Database\Table\Operators::HASH_LEGACY:
                        if (hash_equals($operator['passwd'], md5($credential))) {
                            $result = new Result(Result::SUCCESS, $identity);
                            $this->_operators->update(
                                array(
                                    'passwd' => $this->generateHash($credential),
                                    'password_version' => \Database\Table\Operators::HASH_DEFAULT,
                                ),
                                array('id' => $identity)
                            );
                        } else {
                            $result = new Result(Result::FAILURE_CREDENTIAL_INVALID, null);
                        }
                        break;
                    default:
                        $result = new Result(
                            Result::FAILURE_UNCATEGORIZED,
                            null,
                            array('Unknown password type: ' . $operator['password_version'])
                        );
                }
            } else {
                $result = new Result(Result::FAILURE_IDENTITY_NOT_FOUND, null);
            }
        } catch (\Exception $e) {
            throw new \Zend\Authentication\Adapter\Exception\RuntimeException(
                'Internal authentication error, see web server log for details',
                0,
                $e
            );
        }
        return $result;
    }

    /**
     * Generate hash for given password using default method
     *
     * @param string $password Password, at most 72 bytes (not characters) long due to bcrypt limitation
     * @return string Hash
     * @throws \InvalidArgumentException if password is too long
     * @throws \LogicException if an error occurs
     */
    public function generateHash($password)
    {
        if (strlen($password) > self::PASSWORD_MAX_BYTES) {
            throw new \InvalidArgumentException('Password length exceeds 72 bytes');
        }
        $hash = password_hash($password, PASSWORD_DEFAULT);
        if ($hash === false) {
            // @codeCoverageIgnoreStart
            throw new \LogicException('Error generating password hash');
            // @codeCoverageIgnoreEnd
        }
        return $hash;
    }
}
