<?php

/**
 * Tests for AuthenticationService
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

use Model\Operator\AuthenticationService;

/**
 * Tests for AuthenticationService
 */
class AuthenticationServiceTest extends \Model\Test\AbstractTest
{
    /** {@inheritdoc} */
    protected static $_tables = array('Operators');

    public function getDataSet()
    {
        return new \PHPUnit\DbUnit\DataSet\DefaultDataSet();
    }

    public function testService()
    {
        $service = static::$serviceManager->build('Laminas\Authentication\AuthenticationService');
        $this->assertInstanceOf('Model\Operator\AuthenticationService', $service);
        $this->assertInstanceOf('Model\Operator\AuthenticationAdapter', $service->getAdapter());
    }

    public function testLogin()
    {
        $result = $this->createMock('Laminas\Authentication\Result');
        $result->method('isValid')->willReturn('is_valid');

        $adapter = $this->createMock('Model\Operator\AuthenticationAdapter');
        $adapter->method('setIdentity')->with('user')->willReturnSelf();
        $adapter->method('setCredential')->with('password')->willReturnSelf();
        $adapter->method('authenticate')->willReturn($result);

        $service = $this->createPartialMock(AuthenticationService::class, ['getAdapter', 'authenticate']);
        $service->method('getAdapter')->willReturn($adapter);
        $service->method('authenticate')->with(null)->willReturnCallback(
            function () use ($adapter) {
                return $adapter->authenticate();
            }
        );

        $this->assertEquals('is_valid', $service->login('user', 'password'));
    }

    public function testLoginEmptyUser()
    {
        $service = $this->createPartialMock(AuthenticationService::class, ['getAdapter', 'authenticate']);
        $service->expects($this->never())->method('getAdapter');
        $service->expects($this->never())->method('authenticate');

        $this->assertFalse($service->login('', 'password'));
    }

    public function testChangeIdentityValid()
    {
        $storage = $this->createMock('Laminas\Authentication\Storage\StorageInterface');
        $storage->expects($this->once())->method('write')->with('user');

        $service = $this->createPartialMock(AuthenticationService::class, ['getStorage', 'hasIdentity']);
        $service->method('getStorage')->willReturn($storage);
        $service->expects($this->once())->method('hasIdentity')->willReturn(true);

        $service->changeIdentity('user');
    }

    public function testChangeIdentityUnauthenticated()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Cannot change identity: not authenticated yet');

        $service = $this->createPartialMock(AuthenticationService::class, ['getStorage', 'hasIdentity']);
        $service->expects($this->never())->method('getStorage');
        $service->expects($this->once())->method('hasIdentity')->willReturn(false);

        $service->changeIdentity('user');
    }

    public function testChangeIdentityNoIdentity()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('No identity provided');

        $service = $this->createPartialMock(AuthenticationService::class, ['getStorage', 'hasIdentity']);
        $service->expects($this->never())->method('getStorage');
        $service->method('hasIdentity')->willReturn(true);

        $service->changeIdentity('');
    }
}
