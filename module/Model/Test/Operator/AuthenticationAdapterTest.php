<?php
/**
 * Tests for AuthenticationAdapter
 *
 * Copyright (C) 2011-2017 Holger Schletz <holger.schletz@web.de>
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

use \Zend\Authentication\Result;

/**
 * Tests for AuthenticationAdapter
 */
class AuthenticationAdapterTest extends \Model\Test\AbstractTest
{
    /** {@inheritdoc} */
    protected static $_tables = array('Operators');

    /**
     * Assert that authenticate() will yield result with given properties
     *
     * @param integer $code Expected code
     * @param mixed $identity Expected identity
     * @param array $messages Expected messages
     * @param \Zend\Authentication\Adapter\AdapterInterface $adapter Adapter to test
     */
    public function assertAuthenticationResult($code, $identity, $messages, $adapter)
    {
        $result = $adapter->authenticate();
        $this->assertInstanceOf('Zend\Authentication\Result', $result);
        $this->assertSame($code, $result->getCode());
        $this->assertSame($identity, $result->getIdentity());
        $this->assertSame($messages, $result->getMessages());
    }

    /** {@inheritdoc} */
    public function testInterface()
    {
        $this->assertTrue(true); // Test does not apply to this class
    }

    public function testAuthenticateInvalidIdentity()
    {
        $adapter = $this->getMockBuilder($this->_getClass())
                        ->setConstructorArgs(array(static::$serviceManager->get('Database\Table\Operators')))
                        ->setMethods(array('getIdentity', 'getCredential', 'generateHash'))
                        ->getMock();
        $adapter->method('getIdentity')->willReturn('invalid');
        $adapter->method('getCredential')->willReturn('password');
        $adapter->expects($this->never())->method('generateHash');

        $this->assertAuthenticationResult(Result::FAILURE_IDENTITY_NOT_FOUND, null, array(), $adapter);
        $this->assertTablesEqual(
            $this->_loadDataset()->getTable('operators'),
            $this->getConnection()->createQueryTable(
                'operators',
                'SELECT id, passwd, password_version FROM operators ORDER BY id'
            )
        );
    }

    public function testAuthenticateLegacyFail()
    {
        $adapter = $this->getMockBuilder($this->_getClass())
                        ->setConstructorArgs(array(static::$serviceManager->get('Database\Table\Operators')))
                        ->setMethods(array('getIdentity', 'getCredential', 'generateHash'))
                        ->getMock();
        $adapter->method('getIdentity')->willReturn('user1');
        $adapter->method('getCredential')->willReturn('invalid');
        $adapter->expects($this->never())->method('generateHash');

        $this->assertAuthenticationResult(Result::FAILURE_CREDENTIAL_INVALID, null, array(), $adapter);
        $this->assertTablesEqual(
            $this->_loadDataset()->getTable('operators'),
            $this->getConnection()->createQueryTable(
                'operators',
                'SELECT id, passwd, password_version FROM operators ORDER BY id'
            )
        );
    }

    public function testAuthenticateLegacySuccess()
    {
        $adapter = $this->getMockBuilder($this->_getClass())
                        ->setConstructorArgs(array(static::$serviceManager->get('Database\Table\Operators')))
                        ->setMethods(array('getIdentity', 'getCredential', 'generateHash'))
                        ->getMock();
        $adapter->method('getIdentity')->willReturn('user1');
        $adapter->method('getCredential')->willReturn('password1');
        $adapter->method('generateHash')->willReturn('new_hash');

        $this->assertAuthenticationResult(Result::SUCCESS, 'user1', array(), $adapter);
        $this->assertTablesEqual(
            $this->_loadDataset('AuthenticateLegacySuccess')->getTable('operators'),
            $this->getConnection()->createQueryTable(
                'operators',
                'SELECT id, passwd, password_version FROM operators ORDER BY id'
            )
        );
    }

    public function testAuthenticateDefaultFail()
    {
        $adapter = $this->getMockBuilder($this->_getClass())
                        ->setConstructorArgs(array(static::$serviceManager->get('Database\Table\Operators')))
                        ->setMethods(array('getIdentity', 'getCredential', 'generateHash'))
                        ->getMock();
        $adapter->method('getIdentity')->willReturn('user2');
        $adapter->method('getCredential')->willReturn('invalid');
        $adapter->expects($this->never())->method('generateHash');

        $this->assertAuthenticationResult(Result::FAILURE_CREDENTIAL_INVALID, null, array(), $adapter);
        $this->assertTablesEqual(
            $this->_loadDataset()->getTable('operators'),
            $this->getConnection()->createQueryTable(
                'operators',
                'SELECT id, passwd, password_version FROM operators ORDER BY id'
            )
        );
    }

    public function testAuthenticateDefaultSuccessNoUpdate()
    {
        $adapter = $this->getMockBuilder($this->_getClass())
                        ->setConstructorArgs(array(static::$serviceManager->get('Database\Table\Operators')))
                        ->setMethods(array('getIdentity', 'getCredential', 'generateHash'))
                        ->getMock();
        $adapter->method('getIdentity')->willReturn('user2');
        $adapter->method('getCredential')->willReturn('password2');
        $adapter->expects($this->never())->method('generateHash');

        $this->assertAuthenticationResult(Result::SUCCESS, 'user2', array(), $adapter);
        $this->assertTablesEqual(
            $this->_loadDataset()->getTable('operators'),
            $this->getConnection()->createQueryTable(
                'operators',
                'SELECT id, passwd, password_version FROM operators ORDER BY id'
            )
        );
    }

    public function testAuthenticateDefaultSuccessUpdate()
    {
        $adapter = $this->getMockBuilder($this->_getClass())
                        ->setConstructorArgs(array(static::$serviceManager->get('Database\Table\Operators')))
                        ->setMethods(array('getIdentity', 'getCredential', 'generateHash'))
                        ->getMock();
        $adapter->method('getIdentity')->willReturn('user3');
        $adapter->method('getCredential')->willReturn('password3');
        $adapter->method('generateHash')->willReturn('new_hash');

        $this->assertAuthenticationResult(Result::SUCCESS, 'user3', array(), $adapter);
        $this->assertTablesEqual(
            $this->_loadDataset('AuthenticateDefaultSuccessUpdate')->getTable('operators'),
            $this->getConnection()->createQueryTable(
                'operators',
                'SELECT id, passwd, password_version FROM operators ORDER BY id'
            )
        );
    }

    public function testAuthenticateUnknownHashType()
    {
        $adapter = $this->getMockBuilder($this->_getClass())
                        ->setConstructorArgs(array(static::$serviceManager->get('Database\Table\Operators')))
                        ->setMethods(array('getIdentity', 'getCredential', 'generateHash'))
                        ->getMock();
        $adapter->method('getIdentity')->willReturn('user4');
        $adapter->method('getCredential')->willReturn('password4');
        $adapter->expects($this->never())->method('generateHash');

        $this->assertAuthenticationResult(
            Result::FAILURE_UNCATEGORIZED,
            null,
            array('Unknown password type: 2'),
            $adapter
        );
        $this->assertTablesEqual(
            $this->_loadDataset()->getTable('operators'),
            $this->getConnection()->createQueryTable(
                'operators',
                'SELECT id, passwd, password_version FROM operators ORDER BY id'
            )
        );
    }

    public function testAuthenticateException()
    {
        $exception = new \Exception('original message');

        $operators = $this->createMock('Database\Table\Operators');
        $operators->method('getSql')->willThrowException($exception);

        $adapter = $this->getMockBuilder($this->_getClass())
                        ->setConstructorArgs(array($operators))
                        ->setMethods(array('getIdentity', 'getCredential', 'generateHash'))
                        ->getMock();
        $adapter->method('getIdentity')->willReturn('user');
        $adapter->method('getCredential')->willReturn('password');
        $adapter->expects($this->never())->method('generateHash');

        try {
            $adapter->authenticate();
            $this->fail('Expected exception was not thrown');
        } catch (\Zend\Authentication\Adapter\Exception\RuntimeException $e) {
            $this->assertSame('Internal authentication error, see web server log for details', $e->getMessage());
            $this->assertSame(0, $e->getCode());
            $this->assertSame($exception, $e->getPrevious());
        }
        $this->assertTablesEqual(
            $this->_loadDataset()->getTable('operators'),
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
        $adapter = $this->getMockBuilder($this->_getClass())
                        ->disableOriginalConstructor()
                        ->setMethods(null)
                        ->getMock();
        $hash = $adapter->generateHash($password);
        $this->assertEquals($match, password_verify($testPassword, $hash));
    }

    public function testGenerateHashPasswordTooLong()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Password length exceeds 72 bytes');

        $adapter = $this->getMockBuilder($this->_getClass())
                        ->disableOriginalConstructor()
                        ->setMethods(null)
                        ->getMock();
        $adapter->generateHash(str_repeat('a', 73));
    }
}
