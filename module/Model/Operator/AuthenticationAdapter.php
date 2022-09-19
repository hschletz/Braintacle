<?php

/**
 * Authentication adapter
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

use Database\Table\Operators;
use Laminas\Authentication\Adapter\Exception\RuntimeException as AuthenticationRuntimeException;
use Laminas\Authentication\Result;
use LogicException;
use Throwable;

/**
 * Authentication adapter
 *
 * This adapter should be used for authentication against the application's
 * database. Hashes are automatically converted to a more secure hash after
 * successful authentication if necessary.
 */
class AuthenticationAdapter extends \Laminas\Authentication\Adapter\AbstractAdapter
{
    /**
     * The bcrypt hashing method truncates passwords to 72 bytes.
     */
    const PASSWORD_MAX_BYTES = 72;

    /**
     * Password hash
     * @var string|null
     */
    protected $hash;

    /**
     * Hash type, one of the constants of the Operators table
     * @var int|null
     */
    protected $hashType;

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
     * Get hash.
     */
    public function getHash(): ?string
    {
        if (!$this->hash) {
            $this->fetchHash();
        }

        return $this->hash;
    }

    /**
     * Get hash type.
     */
    public function getHashType(): ?int
    {
        if (!$this->hashType) {
            $this->fetchHash();
        }

        return $this->hashType;
    }

    /**
     * Fetch hash and type from the database.
     *
     * If the identity does not exist, these values will be set to NULL.
     */
    protected function fetchHash(): void
    {
        $sql = $this->_operators->getSql();
        $select = $sql->select();
        $select = $sql->select();
        $select->columns(['passwd', 'password_version'])->where(['id' => $this->getIdentity()]);
        $operator = $sql->prepareStatementForSqlObject($select)->execute()->current();

        if ($operator) {
            $this->hash = $operator['passwd'];
            $this->hashType = $operator['password_version'];
        } else {
            $this->hash = null;
            $this->hashType = null;
        }
    }

    public function authenticate()
    {
        try {
            if ($this->getHash()) {
                $result = $this->verifyHash();
            } else {
                $result = new Result(Result::FAILURE_IDENTITY_NOT_FOUND, null);
            }
        } catch (Throwable $t) {
            throw new AuthenticationRuntimeException(
                'Internal authentication error, see web server log for details',
                0,
                $t
            );
        }
        return $result;
    }

    /**
     * Verify credential against hash.
     */
    public function verifyHash(): Result
    {
        $type = $this->getHashType();
        switch ($type) {
            case Operators::HASH_DEFAULT:
                $result = $this->verifyDefaultHash();
                break;
            case Operators::HASH_LEGACY:
                $result = $this->verifyLegacyHash();
                break;
            default:
                $result = new Result(
                    Result::FAILURE_UNCATEGORIZED,
                    null,
                    ['Unknown password type: ' . $type]
                );
        }

        return $result;
    }

    /**
     * Verify credential using default hashing method.
     */
    public function verifyDefaultHash(): Result
    {
        $hash = $this->getHash();
        if (password_verify($this->getCredential(), $hash)) {
            if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
                $this->updateHash();
            }
            $result = new Result(Result::SUCCESS, $this->getIdentity());
        } else {
            $result = new Result(Result::FAILURE_CREDENTIAL_INVALID, null);
        }

        return $result;
    }

    /**
     * Verify credential using legacy hashing method.
     */
    public function verifyLegacyHash(): Result
    {
        $hash = $this->getHash();
        $credential = $this->getCredential();
        if (hash_equals($hash, md5($credential))) {
            $this->updateHash();
            $result = new Result(Result::SUCCESS, $this->getIdentity());
        } else {
            $result = new Result(Result::FAILURE_CREDENTIAL_INVALID, null);
        }

        return $result;
    }

    /**
     * Update hash in database.
     *
     * @throws LogicException if identity or credential are not set.
     */
    public function updateHash(): void
    {
        $identity = $this->getIdentity();
        $credential = $this->getCredential();
        if (!$identity or !$credential) {
            throw new LogicException('Identity or credential not set');
        }

        $this->hash = $this->generateHash($credential);
        $this->hashType = Operators::HASH_DEFAULT;

        $this->_operators->update(
            [
                'passwd' => $this->hash,
                'password_version' => $this->hashType,
            ],
            ['id' => $identity]
        );
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
