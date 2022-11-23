<?php

/**
 * Tests for Model\Operator\OperatorManager
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
use Model\Operator\AuthenticationService;
use Model\Operator\OperatorManager;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Tests for Model\Operator\OperatorManager
 */
class OperatorManagerTest extends \Model\Test\AbstractTest
{
    /** {@inheritdoc} */
    protected static $_tables = array('Operators');

    public function testGetOperatorsDefaultOrder()
    {
        $model = $this->getModel();
        $resultSet = $model->getOperators();
        $this->assertInstanceOf('Laminas\Db\ResultSet\AbstractResultSet', $resultSet);
        $operators = iterator_to_array($resultSet);
        $this->assertContainsOnlyInstancesOf('Model\Operator\Operator', $operators);
        $this->assertCount(2, $operators);
        $this->assertEquals('user1', $operators[0]['Id']);
        $this->assertEquals('user2', $operators[1]['Id']);
    }

    public function testGetOperatorsCustomOrder()
    {
        $model = $this->getModel();
        $resultSet = $model->getOperators('Id', 'desc');
        $this->assertInstanceOf('Laminas\Db\ResultSet\AbstractResultSet', $resultSet);
        $operators = iterator_to_array($resultSet);
        $this->assertContainsOnlyInstancesOf('Model\Operator\Operator', $operators);
        $this->assertCount(2, $operators);
        $this->assertEquals('user2', $operators[0]['Id']);
        $this->assertEquals('user1', $operators[1]['Id']);
    }

    public function testGetAllIds()
    {
        $model = $this->getModel();
        $ids = $model->getAllIds();
        sort($ids); // Result order is undefined, sort for comparison
        $this->assertEquals(array('user1', 'user2'), $ids);
    }

    public function testGetOperator()
    {
        $model = $this->getModel();
        $operator = $model->getOperator('user1');
        $this->assertInstanceOf('Model\Operator\Operator', $operator);
        $this->assertEquals(
            array(
                'Id' => 'user1',
                'FirstName' => 'First',
                'LastName' => 'Last',
                'MailAddress' => 'test@example.net',
                'Comment' => 'Comment',
            ),
            $operator->getArrayCopy()
        );
    }

    public function testGetOperatorWithoutId()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('No login name supplied');
        $model = $this->getModel();
        $model->getOperator(null);
    }

    public function testGetOperatorWithInvalidArgument()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('No login name supplied');
        $model = $this->getModel();
        $model->getOperator(array('user2'));
    }

    public function testGetOperatorWithInvalidId()
    {
        $this->expectException('RuntimeException');
        $this->expectExceptionMessage('Invalid login name supplied');
        $model = $this->getModel();
        $model->getOperator('invalid');
    }

    public function testCreateOperatorMinimal()
    {
        $adapter = $this->createMock('Model\Operator\AuthenticationAdapter');
        $adapter->method('generateHash')->willReturn('new_hash');

        /** @var MockObject|AuthenticationService */
        $authenticationService = $this->createMock('Model\Operator\AuthenticationService');
        $authenticationService->method('getAdapter')->willReturn($adapter);

        $model = new OperatorManager(
            $authenticationService,
            static::$serviceManager->get(Operators::class)
        );
        $model->createOperator(array('Id' => 'new_id'), 'new_passwd');
        $this->assertTablesEqual(
            $this->loadDataSet('CreateMinimal')->getTable('operators'),
            $this->getConnection()->createQueryTable(
                'operators',
                'SELECT id, firstname, lastname, passwd, password_version, comments, email from operators'
            )
        );
    }

    public function testCreateOperatorFull()
    {
        $adapter = $this->createMock('Model\Operator\AuthenticationAdapter');
        $adapter->method('generateHash')->willReturn('new_hash');

        /** @var MockObject|AuthenticationService */
        $authenticationService = $this->createMock('Model\Operator\AuthenticationService');
        $authenticationService->method('getAdapter')->willReturn($adapter);

        $model = new OperatorManager(
            $authenticationService,
            static::$serviceManager->get(Operators::class)
        );
        $model->createOperator(
            array(
                'Id' => 'new_id',
                'FirstName' => 'new_first',
                'LastName' => 'new_last',
                'MailAddress' => 'new_mail',
                'Comment' => 'new_comment',
                'Password' => 'ignore',
            ),
            'new_passwd'
        );
        $this->assertTablesEqual(
            $this->loadDataSet('CreateFull')->getTable('operators'),
            $this->getConnection()->createQueryTable(
                'operators',
                'SELECT id, firstname, lastname, passwd, password_version, comments, email from operators'
            )
        );
    }

    public function testCreateOperatorWithoutId()
    {
        $model = $this->getModel();
        try {
            $model->createOperator(array(), 'new_passwd');
            $this->fail('Expected Exception was not thrown');
        } catch (\InvalidArgumentException $e) {
            $this->assertEquals('No login name supplied', $e->getMessage());
        }
        $this->assertTablesEqual(
            $this->loadDataSet()->getTable('operators'),
            $this->getConnection()->createQueryTable(
                'operators',
                'SELECT id, firstname, lastname, passwd, password_version, comments, email from operators'
            )
        );
    }

    public function testCreateOperatorWithoutPassword()
    {
        $model = $this->getModel();
        try {
            $model->createOperator(array('Id' => 'id'), '');
            $this->fail('Expected Exception was not thrown');
        } catch (\InvalidArgumentException $e) {
            $this->assertEquals('No password supplied', $e->getMessage());
        }
        $this->assertTablesEqual(
            $this->loadDataSet()->getTable('operators'),
            $this->getConnection()->createQueryTable(
                'operators',
                'SELECT id, firstname, lastname, passwd, password_version, comments, email from operators'
            )
        );
    }

    public function updateOperatorProvider()
    {
        return array(
            array(
                array(
                    'Id' => 'user1', // unchanged
                    'FirstName' => 'new_first',
                    'LastName' => 'new_last',
                    'MailAddress' => 'new_mail',
                    'Comment' => 'new_comment',
                    'Ignored' => 'ignored',
                ),
                null,
                'UpdateAuxilliaryProperties'
            ),
            array(array('Id' => 'new_id'), '', 'UpdateIdentity'),
            array(array(), 'new_password', 'UpdatePassword'),
        );
    }

    /**
     * @dataProvider updateOperatorProvider
     */
    public function testUpdateOperator($data, $password, $dataSet)
    {
        $adapter = $this->createMock('Model\Operator\AuthenticationAdapter');
        $adapter->method('generateHash')->willReturn('new_hash');

        /** @var MockObject|AuthenticationService */
        $authService = $this->createMock('Model\Operator\AuthenticationService');
        $authService->method('getIdentity')->willReturn('user2');
        $authService->method('getAdapter')->willReturn($adapter);
        $authService->expects($this->never())->method('changeIdentity');

        $model = new OperatorManager(
            $authService,
            static::$serviceManager->get(Operators::class)
        );
        $model->updateOperator('user1', $data, $password);
        $this->assertTablesEqual(
            $this->loadDataSet($dataSet)->getTable('operators'),
            $this->getConnection()->createQueryTable(
                'operators',
                'SELECT id, firstname, lastname, passwd, password_version, comments, email FROM operators ORDER BY id'
            )
        );
    }

    public function testUpdateOperatorCurrentIdentity()
    {
        /** @var MockObject|AuthenticationService */
        $authService = $this->createMock('Model\Operator\AuthenticationService');
        $authService->method('getIdentity')->willReturn('user1');
        $authService->expects($this->once())->method('changeIdentity')->with('new_id');

        $model = new OperatorManager(
            $authService,
            static::$serviceManager->get(Operators::class)
        );
        $model->updateOperator('user1', array('Id' => 'new_id'), '');
        $this->assertTablesEqual(
            $this->loadDataSet('UpdateIdentity')->getTable('operators'),
            $this->getConnection()->createQueryTable(
                'operators',
                'SELECT id, firstname, lastname, passwd, password_version, comments, email FROM operators ORDER BY id'
            )
        );
    }

    public function testUpdateOperatorInvalidUser()
    {
        $model = $this->getModel();
        try {
            $model->updateOperator('invalid', array('Id' => 'new_id'), '');
            $this->fail('Expected exception was not thrown');
        } catch (\RuntimeException $e) {
            $this->assertEquals('Invalid user name: invalid', $e->getMessage());

            $this->assertTablesEqual(
                $this->loadDataSet()->getTable('operators'),
                $this->getConnection()->createQueryTable(
                    'operators',
                    'SELECT id, firstname, lastname, passwd, password_version, comments, email from operators ' .
                    'ORDER BY id DESC'
                )
            );
        }
    }

    public function testDeleteOperator()
    {
        $model = $this->getModel();
        $model->deleteOperator('user2');
        $this->assertTablesEqual(
            $this->loadDataSet('Delete')->getTable('operators'),
            $this->getConnection()->createQueryTable(
                'operators',
                'SELECT id, firstname, lastname, passwd, password_version, comments, email from operators'
            )
        );
    }

    public function testDeleteOperatorNonexistent()
    {
        $model = $this->getModel();
        $model->deleteOperator('user3');
        $this->assertTablesEqual(
            $this->loadDataSet()->getTable('operators'),
            $this->getConnection()->createQueryTable(
                'operators',
                'SELECT id, firstname, lastname, passwd, password_version, comments, email from operators'
            )
        );
    }

    public function testDeleteOperatorCurrentUser()
    {
        /** @var MockObject|AuthenticationService */
        $authService = $this->createMock('Model\Operator\AuthenticationService');
        $authService->expects($this->once())->method('getIdentity')->willReturn('user2');

        $model = new OperatorManager(
            $authService,
            static::$serviceManager->get(Operators::class)
        );
        try {
            $model->deleteOperator('user2');
            $this->fail('Expected Exception was not thrown');
        } catch (\RuntimeException $e) {
            $this->assertEquals('Cannot delete account of current user', $e->getMessage());
        }
        $this->assertTablesEqual(
            $this->loadDataSet()->getTable('operators'),
            $this->getConnection()->createQueryTable(
                'operators',
                'SELECT id, firstname, lastname, passwd, password_version, comments, email from operators'
            )
        );
    }
}
