<?php

/**
 * Tests for Model\ClientOrGroup
 *
 * Copyright (C) 2011-2025 Holger Schletz <holger.schletz@web.de>
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

namespace Model\Test;

use Database\Table\Locks;
use Laminas\Db\Adapter\Adapter;
use Mockery;
use Mockery\Mock;
use Model\ClientOrGroup;
use Model\Config;
use Nada\Database\AbstractDatabase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;

class ClientOrGroupTest extends AbstractTestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /** {@inheritdoc} */
    protected static $_tables = ['Locks'];

    protected $_currentTimestamp;

    /** {@inheritdoc} */
    protected function loadDataSet($testName = null)
    {
        // Get current time from database as reference point for all operations.
        $this->_currentTimestamp = new \DateTime(
            $this->getConnection()->createQueryTable(
                'current',
                'SELECT CURRENT_TIMESTAMP AS current'
            )->getValue(0, 'current'),
            new \DateTimeZone('UTC')
        );

        $dataSet = parent::loadDataSet($testName);
        if (in_array('locks', $dataSet->getTableNames())) {
            // Replace offsets with timestamps (current - offset)
            $locks = $dataSet->getTable('locks');
            $count = $locks->getRowCount();
            $replacement = new \PHPUnit\DbUnit\DataSet\ReplacementDataSet($dataSet);
            for ($i = 0; $i < $count; $i++) {
                $offset = $locks->getValue($i, 'since');
                $interval = new \DateInterval(sprintf('PT%dS', trim($offset, '#')));
                $since = clone $this->_currentTimestamp;
                $since->sub($interval);
                $replacement->addFullReplacement($offset, $since->format('Y-m-d H:i:s'));
            }
            return $replacement;
        } else {
            return $dataSet;
        }
    }

    /**
     * Compose a ClientOrGroup mock with stubs for the given methods.
     */
    public function composeMock(array $mockedMethods = []): MockObject & ClientOrGroup
    {
        return $this->getMockForAbstractClass(ClientOrGroup::class, [], '', false, true, true, $mockedMethods);
    }

    /**
     * Compare "locks" table with dataset using fuzzy timestamp match
     *
     * @param string $dataSetName Name of dataset file to compare
     */
    public function assertLocksTableEquals(?string $dataSetName)
    {
        $dataSetTable = $this->loadDataSet($dataSetName)->getTable('locks');
        $queryTable = $this->getConnection()->createQueryTable(
            'locks',
            'SELECT hardware_id, since FROM locks ORDER BY hardware_id'
        );
        $count = $dataSetTable->getRowCount();
        $this->assertEquals($count, $queryTable->getRowCount());
        for ($i = 0; $i < $count; $i++) {
            $dataSetRow = $dataSetTable->getRow($i);
            $queryRow = $queryTable->getRow($i);
            $dataSetDate = new \DateTime($dataSetRow['since']);
            $queryDate = new \DateTime($queryRow['since']);
            $this->assertThat($queryDate->getTimestamp(), $this->equalToWithDelta($dataSetDate->getTimestamp(), 1));
            $this->assertEquals($dataSetRow['hardware_id'], $queryRow['hardware_id']);
        }
    }

    /** {@inheritdoc} */
    public function testInterface()
    {
        $this->assertTrue(true); // Test does not apply to this class
    }

    public static function lockWithDatabaseTimeProvider()
    {
        return array(
            array(42, 60, true, 'LockNew'),
            array(1, 58, true, 'LockReuse'),
            array(1, 62, false, null),
        );
    }

    /**
     * @dataProvider lockWithDatabaseTimeProvider
     */
    public function testLockWithDatabaseTime($id, $timeout, $success, $dataSetName)
    {
        $config = $this->createMock('Model\Config');
        $config->expects($this->once())->method('__get')->with('lockValidity')->willreturn($timeout);

        $serviceManager = $this->createMock(ContainerInterface::class);
        $serviceManager->method('get')->willReturnMap([
            [AbstractDatabase::class, static::$serviceManager->get(AbstractDatabase::class)],
            [Locks::class, static::$serviceManager->get(Locks::class)],
            [Adapter::class, static::$serviceManager->get(Adapter::class)],
            [Config::class, $config],
        ]);

        /** @var Mock|ClientOrGroup */
        $model = Mockery::mock(ClientOrGroup::class)->makePartial();
        $model->setContainer($serviceManager);
        $model['Id'] = $id;

        $this->assertSame($success, $model->lock());
        $this->assertLocksTableEquals($dataSetName);
    }

    public function testLockRaceCondition()
    {
        $sql = $this->createMock('\Laminas\Db\Sql\Sql');
        $sql->method('select')->willReturn(new \Laminas\Db\Sql\Select());

        $locks = $this->createMock('Database\Table\Locks');
        $locks->method('getSql')->willReturn($sql);
        $locks->method('selectWith')->willReturn(new \ArrayIterator());
        $locks->method('insert')->will($this->throwException(new \RuntimeException('race condition')));

        $config = $this->createMock('Model\Config');

        $serviceManager = $this->createMock(ContainerInterface::class);
        $serviceManager->method('get')->willReturnMap([
            [AbstractDatabase::class, static::$serviceManager->get(AbstractDatabase::class)],
            [Locks::class, $locks],
            [Adapter::class, static::$serviceManager->get(Adapter::class)],
            [Config::class, $config],
        ]);

        $model = $this->composeMock();
        $model->setContainer($serviceManager);
        $model['Id'] = 42;

        $this->assertFalse($model->lock());
    }

    public function testUnlockWithoutLock()
    {
        $model = $this->composeMock(['isLocked']);
        $model->expects($this->once())->method('isLocked')->willReturn(false);
        $model->unlock();
    }

    public function testUnlockWithReleasedLock()
    {
        $serviceManager = $this->createMock(ContainerInterface::class);
        $serviceManager->method('get')->willReturnMap([
            [AbstractDatabase::class, static::$serviceManager->get(AbstractDatabase::class)],
            [Locks::class, static::$serviceManager->get(Locks::class)],
            [Adapter::class, static::$serviceManager->get(Adapter::class)],
        ]);

        $model = $this->composeMock(['isLocked']);
        $model->expects($this->once())->method('isLocked')->willReturn(true);
        $model->setContainer($serviceManager);
        $model['Id'] = 1;

        $current = clone $this->_currentTimestamp;
        $current->add(new \DateInterval('PT10S'));

        $expire = new \ReflectionProperty($model, '_lockTimeout');
        $expire->setValue($model, $current);

        $model->unlock();
        $this->assertLocksTableEquals('Unlock');
        $this->assertNull($expire->getValue($model));
    }

    public function testUnlockWithExpiredLock()
    {
        $serviceManager = $this->createMock(ContainerInterface::class);
        $serviceManager->method('get')->willReturnMap([
            [AbstractDatabase::class, static::$serviceManager->get(AbstractDatabase::class)],
            [Adapter::class, static::$serviceManager->get(Adapter::class)],
        ]);

        /** @var MockObject|ClientOrGroup */
        $model = $this->composeMock(['isLocked']);
        $model->expects($this->once())->method('isLocked')->willReturn(true);
        $model->setContainer($serviceManager);

        $current = clone $this->_currentTimestamp;
        $current->sub(new \DateInterval('PT1S'));

        $expire = new \ReflectionProperty($model, '_lockTimeout');
        $expire->setValue($model, $current);

        $message = null;
        /** @psalm-suppress InvalidArgument */
        set_error_handler(function (int $errno, string $errstr) use (&$message) {
            $message = $errstr;
        });
        try {
            $model->unlock();
        } finally {
            restore_error_handler();
        }
        if ($message != 'Lock expired prematurely. Increase lock lifetime.') {
            $this->fail('Expected exception was not thrown.');
        }

        $this->assertLocksTableEquals(null);
        $this->assertNull($expire->getValue($model));
    }

    public function testIsLockedFalse()
    {
        $model = $this->composeMock();
        $this->assertFalse($model->isLocked());
    }

    public function testIsLockedTrue()
    {
        /** @var Mock|ClientOrGroup */
        $model = Mockery::mock(ClientOrGroup::class)->makePartial();

        $expire = new \ReflectionProperty($model, '_lockNestCount');
        $expire->setValue($model, 2);

        $this->assertTrue($model->isLocked());
    }

    public function testNestedLocks()
    {
        $config = $this->createMock('Model\Config');
        $config->method('__get')->with('lockValidity')->willReturn(42);

        $serviceManager = $this->createMock(ContainerInterface::class);
        $serviceManager->method('get')->willReturnMap([
            [AbstractDatabase::class, static::$serviceManager->get(AbstractDatabase::class)],
            [Locks::class, static::$serviceManager->get(Locks::class)],
            [Adapter::class, static::$serviceManager->get(Adapter::class)],
            [Config::class, $config],
        ]);

        $model = $this->composeMock();
        $model->setContainer($serviceManager);
        $model['Id'] = 23;

        $this->assertTrue($model->lock());
        $this->assertTrue($model->lock());
        $model->unlock();
        $this->assertTrue($model->isLocked());
        $model->unlock();
        $this->assertFalse($model->isLocked());
    }
}
