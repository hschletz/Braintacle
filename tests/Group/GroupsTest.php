<?php

namespace Braintacle\Test\Group;

use Braintacle\Database\Migration;
use Braintacle\Database\Migrations;
use Braintacle\Direction;
use Braintacle\Group\Groups;
use Braintacle\Group\Members\ExcludedClient;
use Braintacle\Group\Members\ExcludedColumn;
use Braintacle\Group\Members\Member;
use Braintacle\Group\Members\MembersColumn;
use Braintacle\Group\Membership;
use Braintacle\Test\DatabaseConnection;
use Braintacle\Test\DataProcessorTestTrait;
use Braintacle\Transformer\DateTime;
use Braintacle\Transformer\DateTimeTransformer;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Model\Group\Group;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
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

    public static function getMembersProvider()
    {
        return [
            'group4' => [MembersColumn::Id, Direction::Ascending, 4, [[1, Membership::Automatic]]],
            'group5' => [MembersColumn::Id, Direction::Ascending, 5, [[2, Membership::Manual]]],
            'group6_asc' => [
                MembersColumn::Membership,
                Direction::Ascending,
                6,
                [
                    [2, Membership::Automatic],
                    [1, Membership::Manual],
                ],
            ],
            'group6_desc' => [
                MembersColumn::Membership,
                Direction::Descending,
                6,
                [
                    [1, Membership::Manual],
                    [2, Membership::Automatic],
                ],
            ],
        ];
    }

    #[DataProvider('getMembersProvider')]
    public function testGetMembers(MembersColumn $order, Direction $direction, int $groupId, array $expected)
    {
        DatabaseConnection::with(function (Connection $connection) use ($order, $direction, $groupId, $expected): void {
            DatabaseConnection::initializeTable('hardware', ['id', 'deviceid', 'name', 'userid', 'lastdate'], [
                [1, 'id1', 'name1', 'user1', '2025-03-15T17:11:14'],
                [2, 'id2', 'name2', 'user2', '2015-08-11T14:18:50'],
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

            $dateTimeTransformer = $this->createStub(DateTimeTransformer::class);
            $dateTimeTransformer->method('transform')->willReturn(new DateTimeImmutable());

            $dataProcessor = $this->createDataProcessor([DateTimeTransformer::class => $dateTimeTransformer]);

            $group = $this->createMock(Group::class);
            $group->expects($this->once())->method('update');
            $group->method('__get')->with('id')->willReturn($groupId);

            $groups = new Groups($connection, $dataProcessor);
            $members = $groups->getMembers($group, $order, $direction);
            $this->assertEquals(
                $expected,
                array_map(
                    fn($member) => [$member->id, $member->membership],
                    [...$members],
                ),
            );
        });
    }

    public function testGetExcludedClients()
    {
        DatabaseConnection::with(function (Connection $connection): void {
            $groupId = 4;
            $order = ExcludedColumn::Id;
            $direction = Direction::Ascending;
            $inventoryDate = '2025-03-15T12:11:14';
            $expectedInventoryDate = new DateTimeImmutable($inventoryDate);

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
                ->with(
                    $this->callback(
                        // Compare objects instead of strings to acommodate for
                        // different timestamp formats (Some databases use 'T'
                        // as separator, others use ' ')
                        fn(string $timestamp) => new DateTimeImmutable($timestamp) == $expectedInventoryDate
                    )
                )
                ->willReturn($expectedInventoryDate);

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
            $this->assertEquals($expectedInventoryDate, $client->inventoryDate);
        });
    }
}
