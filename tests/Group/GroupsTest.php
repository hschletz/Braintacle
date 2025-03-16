<?php

namespace Braintacle\Test\Group;

use Braintacle\Database\Migration;
use Braintacle\Database\Migrations;
use Braintacle\Direction;
use Braintacle\Group\Groups;
use Braintacle\Group\Members\ExcludedClient;
use Braintacle\Group\Members\ExcludedColumn;
use Braintacle\Test\DatabaseConnection;
use Braintacle\Test\DataProcessorTestTrait;
use Braintacle\Transformer\DateTime;
use Braintacle\Transformer\DateTimeTransformer;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Model\Group\Group;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Traversable;

#[CoversClass(Groups::class)]
#[UsesClass(Migration::class)]
#[UsesClass(Migrations::class)]
#[UsesClass(DateTime::class)]
final class GroupsTest extends TestCase
{
    use DataProcessorTestTrait;

    public function testGetExcludedClients()
    {
        DatabaseConnection::with(function (Connection $connection): void {
            $groupId = 4;
            $order = ExcludedColumn::Id;
            $direction = Direction::Ascending;
            $inventoryDate = '2025-03-15T12:11:14';

            DatabaseConnection::initializeTable('hardware', ['id', 'deviceid', 'name', 'userid', 'lastdate'], [
                [1, 'id1', 'name1', 'user1', '2025-03-15T17:11:14'],
                [2, 'id2', 'name2', 'user2', $inventoryDate],
                [3, 'id3', 'name3', 'user3', '2025-03-15T17:11:14'],
                [4, '_SYSTEMGROUP_', 'group1', null, '2025-03-15T17:11:14'],
                [5, '_SYSTEMGROUP_', 'group2', null, '2025-03-15T17:11:14'],
                [6, '_SYSTEMGROUP_', 'group3', null, '2025-03-15T17:11:14'],
            ]);
            DatabaseConnection::initializeTable('groups_cache', ['hardware_id', 'group_id', 'static'], [
                [1, 4, 0],
                [1, 5, 2],
                [2, 4, 2],
                [2, 5, 1],
                [1, 6, 1],
                [2, 6, 0],
            ]);

            $dateTimeTransformer = $this->createMock(DateTimeTransformer::class);
            $dateTimeTransformer
                ->method('transform')
                ->with($inventoryDate)
                ->willReturn(new DateTimeImmutable($inventoryDate));

            $dataProcessor = $this->createDataProcessor([DateTimeTransformer::class => $dateTimeTransformer]);

            $groups = new Groups($connection, $dataProcessor);

            $group = $this->createMock(Group::class);
            $group->method('__get')->with('id')->willReturn($groupId);
            $group->expects($this->once())->method('update');

            /** @var Traversable */
            $result = $groups->getExcludedClients($group, $order, $direction);
            /** @var ExcludedClient[] */
            $result = iterator_to_array($result);
            $this->assertCount(1, $result);

            $client = $result[0];
            $this->assertEquals(2, $client->id);
            $this->assertEquals('name2', $client->name);
            $this->assertEquals('user2', $client->userName);
            $this->assertEquals(new DateTimeImmutable($inventoryDate), $client->inventoryDate);
        });
    }
}
