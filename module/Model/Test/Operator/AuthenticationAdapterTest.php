<?php

/**
 * Tests for AuthenticationAdapter
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

namespace Model\Test\Operator;

use Database\Table\Operators;
use Laminas\Authentication\Adapter\Exception\RuntimeException as AuthenticationRuntimeException;
use Laminas\Authentication\Result;
use LogicException;
use Mockery;
use Model\Operator\AuthenticationAdapter;

/**
 * Tests for AuthenticationAdapter
 */
class AuthenticationAdapterTest extends \Model\Test\AbstractTest
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /** {@inheritdoc} */
    protected static $_tables = array('Operators');

    /**
     * Assert that authenticate() will yield result with given properties
     *
     * @param Result result object to test
     * @param integer $code Expected code
     * @param mixed $identity Expected identity
     * @param array $messages Expected messages
     */
    public function assertAuthenticationResult($result, $code, $identity, $messages = [])
    {
        $this->assertInstanceOf(Result::class, $result);
        $this->assertSame($code, $result->getCode());
        $this->assertSame($identity, $result->getIdentity());
        $this->assertSame($messages, $result->getMessages());
    }

    public function testInterface()
    {
        $this->assertTrue(true); // Test does not apply to this class
    }

    public function testGetHashValidUser()
    {
        $adapter = new AuthenticationAdapter(static::$serviceManager->get(Operators::class));
        $adapter->setIdentity('user2');
        $this->assertEquals('hash2', $adapter->getHash());
    }

    public function testGetHashInvalidUser()
    {
        $adapter = new AuthenticationAdapter(static::$serviceManager->get(Operators::class));
        $adapter->setIdentity('invalid');
        $this->assertNull($adapter->getHash());
    }

    public function testGetHashTypeValidUser()
    {
        $adapter = new AuthenticationAdapter(static::$serviceManager->get(Operators::class));
        $adapter->setIdentity('user2');
        $this->assertEquals(1, $adapter->getHashType());
    }

    public function testGetHashTypeInvalidUser()
    {
        $adapter = new AuthenticationAdapter(static::$serviceManager->get(Operators::class));
        $adapter->setIdentity('invalid');
        $this->assertNull($adapter->getHashType());
    }

    public function testAuthenticateValidUser()
    {
        $result = $this->createStub(Result::class);

        $adapter = $this->createPartialMock(AuthenticationAdapter::class, ['getHash', 'verifyHash']);
        $adapter->method('getHash')->willReturn('hash');
        $adapter->method('verifyHash')->willReturn($result);

        $this->assertSame($result, $adapter->authenticate());
    }

    public function testAuthenticateInvalidUser()
    {
        $adapter = $this->createPartialMock(AuthenticationAdapter::class, ['getHash', 'verifyHash']);
        $adapter->method('getHash')->willReturn(null);
        $adapter->expects($this->never())->method('verifyHash');

        $this->assertAuthenticationResult($adapter->authenticate(), Result::FAILURE_IDENTITY_NOT_FOUND, null);
    }

    public function testAuthenticateGetHashThrowsException()
    {
        $exception = new \RuntimeException();

        $adapter = $this->createPartialMock(AuthenticationAdapter::class, ['getHash', 'verifyHash']);
        $adapter->method('getHash')->willThrowException($exception);
        $adapter->expects($this->never())->method('verifyHash');

        $this->expectException(AuthenticationRuntimeException::class);
        $this->expectExceptionMessage('Internal authentication error, see web server log for details');

        $adapter->authenticate();
    }

    public function testAuthenticateVerifyHashThrowsException()
    {
        $exception = new \RuntimeException();

        $adapter = $this->createPartialMock(AuthenticationAdapter::class, ['getHash', 'verifyHash']);
        $adapter->method('getHash')->willReturn('hash');
        $adapter->method('verifyHash')->willThrowException($exception);

        $this->expectException(AuthenticationRuntimeException::class);
        $this->expectExceptionMessage('Internal authentication error, see web server log for details');

        $adapter->authenticate();
    }

    public function testVerifyHashDefault()
    {
        $result = $this->createStub(Result::class);

        $adapter = $this->createPartialMock(
            AuthenticationAdapter::class,
            ['getHashType', 'verifyDefaultHash', 'verifyLegacyHash']
        );
        $adapter->method('getHashType')->willReturn(Operators::HASH_DEFAULT);
        $adapter->method('verifyDefaultHash')->willReturn($result);
        $adapter->expects($this->never())->method('verifyLegacyHash');

        $this->assertSame($result, $adapter->verifyHash());
    }

    public function testVerifyHashLegacy()
    {
        $result = $this->createStub(Result::class);

        $adapter = $this->createPartialMock(
            AuthenticationAdapter::class,
            ['getHashType', 'verifyDefaultHash', 'verifyLegacyHash']
        );
        $adapter->method('getHashType')->willReturn(Operators::HASH_LEGACY);
        $adapter->expects($this->never())->method('verifyDefaultHash');
        $adapter->method('verifyLegacyHash')->willReturn($result);

        $this->assertSame($result, $adapter->verifyHash());
    }

    public function testVerifyHashInvalid()
    {
        $adapter = $this->createPartialMock(
            AuthenticationAdapter::class,
            ['getHashType', 'verifyDefaultHash', 'verifyLegacyHash', 'getIdentity']
        );
        $adapter->method('getHashType')->willReturn(2);
        $adapter->expects($this->never())->method('verifyDefaultHash');
        $adapter->expects($this->never())->method('verifyLegacyHash');
        $adapter->method('getIdentity')->willReturn('identity');

        $this->assertAuthenticationResult(
            $adapter->verifyHash(),
            Result::FAILURE_UNCATEGORIZED,
            null,
            ['Unknown password type: 2']
        );
    }

    public function testVerifyDefaultHashDefaultFail()
    {
        $adapter = $this->createPartialMock(
            AuthenticationAdapter::class,
            ['getHash', 'getCredential', 'getIdentity', 'updateHash']
        );
        $adapter->method('getHash')->willReturn('$2y$10$aA/.DiN0Vhb0emJ8jkRScuLb4ncdBbLvnUdM7GggoPJSm4r8EPQ6S');
        $adapter->method('getCredential')->willReturn('password'); // invalid, real password is "password2"
        $adapter->expects($this->never())->method('getIdentity');
        $adapter->expects($this->never())->method('updateHash');

        $this->assertAuthenticationResult(
            $adapter->verifyDefaultHash(),
            Result::FAILURE_CREDENTIAL_INVALID,
            null
        );
    }

    public function testVerifyDefaultHashDefaultSuccessNoRehash()
    {
        $adapter = $this->createPartialMock(
            AuthenticationAdapter::class,
            ['getHash', 'getCredential', 'getIdentity', 'updateHash']
        );
        $adapter->method('getHash')->willReturn('$2y$10$aA/.DiN0Vhb0emJ8jkRScuLb4ncdBbLvnUdM7GggoPJSm4r8EPQ6S');
        $adapter->method('getCredential')->willReturn('password2');
        $adapter->method('getIdentity')->willReturn('identity');
        $adapter->expects($this->never())->method('updateHash');

        $this->assertAuthenticationResult($adapter->verifyDefaultHash(), Result::SUCCESS, 'identity');
    }

    public function testVerifyDefaultHashDefaultSuccessWithRehash()
    {
        $adapter = $this->createPartialMock(
            AuthenticationAdapter::class,
            ['getHash', 'getCredential', 'getIdentity', 'updateHash']
        );
        $adapter->method('getHash')->willReturn('$1$i.L4MX9p$bjGxsIMKCB/WLvDkBXRdu1');
        $adapter->method('getCredential')->willReturn('password3');
        $adapter->method('getIdentity')->willReturn('identity');
        $adapter->expects($this->once())->method('updateHash');

        $this->assertAuthenticationResult($adapter->verifyDefaultHash(), Result::SUCCESS, 'identity');
    }

    public function testVerifyLegacyHashFail()
    {
        $adapter = $this->createPartialMock(
            AuthenticationAdapter::class,
            ['getHash', 'getCredential', 'getIdentity', 'updateHash']
        );
        $adapter->method('getHash')->willReturn('7c6a180b36896a0a8c02787eeafb0e4c');
        $adapter->method('getCredential')->willReturn('password'); // invalid, real password is "password1"
        $adapter->expects($this->never())->method('getIdentity');
        $adapter->expects($this->never())->method('updateHash');

        $this->assertAuthenticationResult($adapter->verifyLegacyHash(), Result::FAILURE_CREDENTIAL_INVALID, null);
    }

    public function testVerifyLegacyHashSuccess()
    {
        $adapter = $this->createPartialMock(
            AuthenticationAdapter::class,
            ['getHash', 'getCredential', 'getIdentity', 'updateHash']
        );
        $adapter->method('getHash')->willReturn('7c6a180b36896a0a8c02787eeafb0e4c');
        $adapter->method('getCredential')->willReturn('password1');
        $adapter->method('getIdentity')->willReturn('identity');
        $adapter->expects($this->once())->method('updateHash');

        $this->assertAuthenticationResult($adapter->verifyLegacyHash(), Result::SUCCESS, 'identity');
    }

    public function testUpdateHash()
    {
        $adapter = Mockery::mock(
            AuthenticationAdapter::class,
            [static::$serviceManager->get(Operators::class)]
        )->makePartial();
        $adapter->shouldReceive('getIdentity')->andReturn('user1');
        $adapter->shouldReceive('getCredential')->andReturn('credential');
        $adapter->shouldReceive('generateHash')->andReturn('new_hash');

        $adapter->updateHash();

        $this->assertEquals('new_hash', $adapter->getHash());
        $this->assertSame(Operators::HASH_DEFAULT, $adapter->getHashType());

        $this->assertTablesEqual(
            $this->loadDataSet('UpdateHash')->getTable('operators'),
            $this->getConnection()->createQueryTable(
                'operators',
                'SELECT id, passwd, password_version FROM operators ORDER BY id'
            )
        );
    }

    public function testUpdateHashUninitializedIdentity()
    {
        $adapter = Mockery::mock(
            AuthenticationAdapter::class,
            [static::$serviceManager->get(Operators::class)]
        )->makePartial();
        $adapter->shouldReceive('getIdentity')->andReturnNull();
        $adapter->shouldReceive('getIdentity')->andReturn('credential');

        try {
            $adapter->updateHash();
            $this->fail('Expected exception was not thrown.');
        } catch (LogicException $e) {
            $this->assertEquals('Identity or credential not set', $e->getMessage());
        }

        // unchanged
        $this->assertTablesEqual(
            $this->loadDataSet()->getTable('operators'),
            $this->getConnection()->createQueryTable(
                'operators',
                'SELECT id, passwd, password_version FROM operators ORDER BY id'
            )
        );
    }

    public function testUpdateHashUninitializedCredential()
    {
        $adapter = Mockery::mock(
            AuthenticationAdapter::class,
            [static::$serviceManager->get(Operators::class)]
        )->makePartial();
        $adapter->shouldReceive('getIdentity')->andReturn('identity');
        $adapter->shouldReceive('getIdentity')->andReturnNull();

        try {
            $adapter->updateHash();
            $this->fail('Expected exception was not thrown.');
        } catch (LogicException $e) {
            $this->assertEquals('Identity or credential not set', $e->getMessage());
        }

        // unchanged
        $this->assertTablesEqual(
            $this->loadDataSet()->getTable('operators'),
            $this->getConnection()->createQueryTable(
                'operators',
                'SELECT id, passwd, password_version FROM operators ORDER BY id'
            )
        );
    }

    public function generateHashProvider()
    {
        // The hash function's behavior for multibyte character sets and other
        // edge cases is poorly documented. These tests ensure that we are
        // making the correct assumptions.
        return array(
            array('test', 'test', true), // simple password
            array("test\xC3\x84test", "test\xC3\x84test", true), // password with UTF-8 multibyte character
            array("test\xC3\x84", "test", false), // no cutoff at non-ASCII character
            array(str_repeat('a', 72), str_repeat('a', 72), true), // maximum length
            array(str_repeat('a', 72), str_repeat('a', 71), false), // verify that implementation does not add NUL byte
        );
    }

    /**
     * @dataProvider generateHashProvider
     */
    public function testGenerateHash($password, $testPassword, $match)
    {
        $adapter = $this->createPartialMock(AuthenticationAdapter::class, []);

        $hash = $adapter->generateHash($password);
        $this->assertEquals($match, password_verify($testPassword, $hash));
    }

    public function testGenerateHashPasswordTooLong()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Password length exceeds 72 bytes');

        $adapter = $this->createPartialMock(AuthenticationAdapter::class, []);
        $adapter->generateHash(str_repeat('a', 73));
    }
}
