<?php

/**
 * Tests for Model\Group\Group
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

namespace Model\Test\Group;

use Database\Table\Clients;
use Database\Table\GroupInfo;
use Database\Table\GroupMemberships;
use DateTimeImmutable;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Model\Config;
use Model\Group\Group;
use Psr\Clock\ClockInterface;
use Psr\Container\ContainerInterface;
use Random\Randomizer;

class GroupTest extends AbstractGroupTestCase
{
    use MockeryPHPUnitIntegration;

    public static function updateProvider()
    {
        return array(
            array(true, false, null, true, null), // force update, but no query
            array(true, true, null, false, null), // force update, but locking fails
            array(false, true, new \DateTime('2015-07-23 20:21:00'), true, null), // not expired yet
            array(true, true, new \DateTime('2015-07-23 20:21:00'), true, 'Update'), // not expired, but forced
            array(false, true, new \DateTime('2015-07-23 20:19:00'), true, 'Update'), // expired
            array(false, true, null, true, 'Update'), // no cache yet
        );
    }
    /**
     * @dataProvider updateProvider
     */
    public function testUpdate($force, $setSql, $expires, $lockSuccess, $dataSet)
    {
        $now = new DateTimeImmutable('2015-07-23 20:20:00');

        $clock = $this->createStub(ClockInterface::class);
        $clock->method('now')->willReturn($now);

        // Builtin Randomizer class final and cannot be mocked. Create proxy instead.
        $randomizer = Mockery::mock(new Randomizer());
        $randomizer->shouldReceive('getInt')->with(0, 60)->andReturn(42);

        $config = $this->createMock('Model\Config');
        $config->method('__get')->will(
            $this->returnValueMap(
                array(
                    array('groupCacheExpirationInterval', 600),
                    array('groupCacheExpirationFuzz', 60),
                )
            )
        );

        $serviceManager = $this->createStub(ContainerInterface::class);
        $serviceManager->method('get')
            ->willReturnMap([
                [Clients::class, static::$serviceManager->get(Clients::class)],
                [GroupInfo::class, $this->_groupInfo],
                [GroupMemberships::class, static::$serviceManager->get(GroupMemberships::class)],
                [ClockInterface::class, $clock],
                [Randomizer::class, $randomizer],
                [Config::class, $config],
            ]);

        $model = $this->createPartialMock(Group::class, ['lock', 'unlock']);
        $model->method('lock')->willReturn($lockSuccess);
        if ($dataSet !== null) {
            $model->expects($this->once())->method('unlock');
        }
        $model->setContainer($serviceManager);
        $model['Id'] = 10;
        $model['DynamicMembersSql'] = $setSql ? 'SELECT id FROM hardware WHERE id IN(2,3,4,5)' : null;
        $model['CacheCreationDate'] = null;
        $model['CacheExpirationDate'] = $expires;

        $model->update($force);
        // CacheCreationDate is only updated when there was data to alter ($dataSet !== null)
        $this->assertEquals(
            ($dataSet === null) ? null : $now,
            $model['CacheCreationDate']
        );
        // CacheExpirationDate is either updated ($dataSet !== null) or kept at initialized value
        $this->assertEquals(
            ($dataSet === null) ? $expires : new \DateTime('2015-07-23 20:30:42'),
            $model['CacheExpirationDate']
        );
        $this->assertTablesEqual(
            $this->loadDataSet($dataSet)->getTable('groups'),
            $this->getConnection()->createQueryTable(
                'groups',
                'SELECT hardware_id, request, create_time, revalidate_from FROM groups ORDER BY hardware_id'
            )
        );
    }
}
