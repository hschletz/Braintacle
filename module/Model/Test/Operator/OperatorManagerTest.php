<?php
/**
 * Tests for Model\Operator\OperatorManager
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

namespace Model\Test\Operator;

/**
 * Tests for Model\Operator\OperatorManager
 */
class OperatorManagerTest extends \Model\Test\AbstractTest
{
    /** {@inheritdoc} */
    protected static $_tables = array('Operators');

    public function testFetchAllDefaultOrder()
    {
        $model = $this->_getModel();
        $resultSet = $model->fetchAll();
        $this->assertInstanceOf('Zend\Db\ResultSet\AbstractResultSet', $resultSet);
        $operators = iterator_to_array($resultSet);
        $this->assertContainsOnlyInstancesOf('Model\Operator\Operator', $operators);
        $this->assertCount(2, $operators);
        $this->assertEquals('user1', $operators[0]['Id']);
        $this->assertEquals('user2', $operators[1]['Id']);
    }

    public function testFetchAllCustomOrder()
    {
        $model = $this->_getModel();
        $resultSet = $model->fetchAll('Id', 'desc');
        $this->assertInstanceOf('Zend\Db\ResultSet\AbstractResultSet', $resultSet);
        $operators = iterator_to_array($resultSet);
        $this->assertContainsOnlyInstancesOf('Model\Operator\Operator', $operators);
        $this->assertCount(2, $operators);
        $this->assertEquals('user2', $operators[0]['Id']);
        $this->assertEquals('user1', $operators[1]['Id']);
    }

    public function testGetAllIds()
    {
        $model = $this->_getModel();
        $this->assertEquals(array('user1', 'user2'), $model->getAllIds());
    }

    public function testGet()
    {
        $model = $this->_getModel();
        $operator = $model->get('user1');
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

    public function testGetWithoutId()
    {
        $this->setExpectedException('InvalidArgumentException', 'No login name supplied');
        $model = $this->_getModel();
        $model->get(null);
    }

    public function testGetWithInvalidArgument()
    {
        $this->setExpectedException('InvalidArgumentException', 'No login name supplied');
        $model = $this->_getModel();
        $model->get(array('user2'));
    }

    public function testGetWithInvalidId()
    {
        $this->setExpectedException('RuntimeException', 'Invalid login name supplied');
        $model = $this->_getModel();
        $model->get('invalid');
    }

    public function testCreateMinimal()
    {
        $model = $this->_getModel();
        $model->create(array('Id' => 'new_id'), 'new_passwd');
        $this->assertTablesEqual(
            $this->_loadDataset('CreateMinimal')->getTable('operators'),
            $this->getConnection()->createQueryTable('operators', 'SELECT * from operators')
        );
        $auth = clone \Library\Application::getService('Library\AuthenticationService');
        $this->assertFalse($auth->hasIdentity());
        $this->assertTrue($auth->login('new_id', 'new_passwd'));
        $this->assertEquals('new_id', $auth->getIdentity());
    }

    public function testCreateFull()
    {
        $model = $this->_getModel();
        $model->create(
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
            $this->_loadDataset('CreateFull')->getTable('operators'),
            $this->getConnection()->createQueryTable('operators', 'SELECT * from operators')
        );
        $auth = clone \Library\Application::getService('Library\AuthenticationService');
        $this->assertFalse($auth->hasIdentity());
        $this->assertTrue($auth->login('new_id', 'new_passwd'));
        $this->assertEquals('new_id', $auth->getIdentity());
    }

    public function testCreateWithoutId()
    {
        $model = $this->_getModel();
        try {
            $model->create(array(), 'new_passwd');
            $this->fail('Expected Exception was not thrown');
        } catch (\InvalidArgumentException $e) {
            $this->assertEquals('No login name supplied', $e->getMessage());
        }
        $this->assertTablesEqual(
            $this->_loadDataset()->getTable('operators'),
            $this->getConnection()->createQueryTable('operators', 'SELECT * from operators')
        );
    }

    public function testCreateWithoutPassword()
    {
        $model = $this->_getModel();
        try {
            $model->create(array('Id' => 'id'), '');
            $this->fail('Expected Exception was not thrown');
        } catch (\InvalidArgumentException $e) {
            $this->assertEquals('No password supplied', $e->getMessage());
        }
        $this->assertTablesEqual(
            $this->_loadDataset()->getTable('operators'),
            $this->getConnection()->createQueryTable('operators', 'SELECT * from operators')
        );
    }

    public function updateProvider()
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
     * @dataProvider updateProvider
     */
    public function testUpdate($data, $password, $dataSet)
    {
        $authService = $this->getMock('Library\Authentication\AuthenticationService');
        $authService->method('getIdentity')->willReturn('user2');
        $authService->expects($this->never())->method('changeIdentity');

        $model = $this->_getModel(array('Library\AuthenticationService' => $authService));
        $model->update('user1', $data, $password);
        $this->assertTablesEqual(
            $this->_loadDataSet($dataSet)->getTable('operators'),
            $this->getConnection()->createQueryTable(
                'operators',
                'SELECT id, firstname, lastname, passwd, comments, email FROM operators ORDER BY id'
            )
        );
    }

    public function testUpdateCurrentIdentity()
    {
        $authService = $this->getMock('Library\Authentication\AuthenticationService');
        $authService->method('getIdentity')->willReturn('user1');
        $authService->expects($this->once())->method('changeIdentity')->with('new_id');

        $model = $this->_getModel(array('Library\AuthenticationService' => $authService));
        $model->update('user1', array('Id' => 'new_id'), '');
        $this->assertTablesEqual(
            $this->_loadDataSet('UpdateIdentity')->getTable('operators'),
            $this->getConnection()->createQueryTable(
                'operators',
                'SELECT id, firstname, lastname, passwd, comments, email FROM operators ORDER BY id'
            )
        );
    }

    public function testUpdateInvalidUser()
    {
        $model = $this->_getModel();
        try {
            $model->update('invalid', array('Id' => 'new_id'), '');
            $this->fail('Expected exception was not thrown');
        } catch (\RuntimeException $e) {
            $this->assertEquals('Invalid user name: invalid', $e->getMessage());
            $this->assertTablesEqual(
                $this->_loadDataSet()->getTable('operators'),
                $this->getConnection()->createQueryTable(
                    'operators',
                    'SELECT * FROM operators ORDER BY id DESC'
                )
            );
        }
    }

    public function testDelete()
    {
        $model = $this->_getModel();
        $model->delete('user2');
        $this->assertTablesEqual(
            $this->_loadDataset('Delete')->getTable('operators'),
            $this->getConnection()->createQueryTable('operators', 'SELECT * from operators')
        );
    }

    public function testDeleteNonexistent()
    {
        $model = $this->_getModel();
        $model->delete('user3');
        $this->assertTablesEqual(
            $this->_loadDataset()->getTable('operators'),
            $this->getConnection()->createQueryTable('operators', 'SELECT * from operators')
        );
    }

    public function testDeleteCurrentUser()
    {
        $authService = $this->getMock('Library\Authentication\AuthenticationService');
        $authService->expects($this->once())->method('getIdentity')->willReturn('user2');
        $model = $this->_getModel(array('Library\AuthenticationService' => $authService));
        try {
            $model->delete('user2');
            $this->fail('Expected Exception was not thrown');
        } catch (\RuntimeException $e) {
            $this->assertEquals('Cannot delete account of current user', $e->getMessage());
        }
        $this->assertTablesEqual(
            $this->_loadDataset()->getTable('operators'),
            $this->getConnection()->createQueryTable('operators', 'SELECT * from operators')
        );
    }
}
