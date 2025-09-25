<?php

namespace Braintacle\Test\Group;

use Braintacle\Database\Migration;
use Braintacle\Database\Migrations;
use Braintacle\Database\Table;
use Braintacle\Direction;
use Braintacle\Group\Groups;
use Braintacle\Group\Members\ExcludedClient;
use Braintacle\Group\Members\ExcludedColumn;
use Braintacle\Group\Members\MembersColumn;
use Braintacle\Group\Membership;
use Braintacle\Search\Search;
use Braintacle\Search\SearchParams;
use Braintacle\Test\DatabaseConnection;
use Braintacle\Test\DataProcessorTestTrait;
use Braintacle\Time;
use Braintacle\Transformer\DateTime;
use Braintacle\Transformer\DateTimeTransformer;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Exception;
use Formotron\DataProcessor;
use Laminas\Db\Sql\Select;
use Laminas\Db\Sql\Sql;
use LogicException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Model\Client\Client;
use Model\Group\Group;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Traversable;

#[CoversClass(Groups::class)]
#[UsesClass(Migration::class)]
#[UsesClass(Migrations::class)]
#[UsesClass(DateTime::class)]
final class GroupsTest extends TestCase
{
    use DataProcessorTestTrait;
    use MockeryPHPUnitIntegration;

    private function createGroups(
        ?Connection $connection = null,
        ?DataProcessor $dataProcessor = null,
        ?Search $search = null,
        ?Sql $sql = null,
        ?Time $time = null,
    ): Groups {
        return new Groups(
            $connection ?? $this->createStub(Connection::class),
            $dataProcessor ?? $this->createStub(DataProcessor::class),
            $search ?? $this->createStub(Search::class),
            $sql ?? $this->createStub(Sql::class),
            $time ?? $this->createStub(Time::class),
        );
    }

    private function createGroupsMock(
        array $methods,
        ?Connection $connection = null,
        ?DataProcessor $dataProcessor = null,
        ?Search $search = null,
        ?Sql $sql = null,
        ?Time $time = null,
    ): MockObject | Groups {
        return $this->getMockBuilder(Groups::class)->onlyMethods($methods)->setConstructorArgs([
            $connection ?? $this->createStub(Connection::class),
            $dataProcessor ?? $this->createStub(DataProcessor::class),
            $search ?? $this->createStub(Search::class),
            $sql ?? $this->createStub(Sql::class),
            $time ?? $this->createStub(Time::class),
        ])->getMock();
    }

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
            DatabaseConnection::initializeTable(
                Table::Groups,
                ['id', 'deviceid', 'name', 'userid', 'lastdate'],
                [
                    [1, 'id1', 'name1', 'user1', '2025-03-15T17:11:14'],
                    [2, 'id2', 'name2', 'user2', '2015-08-11T14:18:50'],
                    [3, 'id3', 'name3', 'user3', '2025-03-15T17:11:14'],
                    [4, '_SYSTEMGROUP_', 'group1', null, '2025-03-15T17:11:14'],
                    [5, '_SYSTEMGROUP_', 'group2', null, '2025-03-15T17:11:14'],
                    [6, '_SYSTEMGROUP_', 'group3', null, '2025-03-15T17:11:14'],
                ],
            );
            DatabaseConnection::initializeTable(Table::GroupMemberships, ['hardware_id', 'group_id', 'static'], [
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

            $groups = $this->createGroups($connection, $dataProcessor);
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

            DatabaseConnection::initializeTable(
                Table::Groups,
                ['id', 'deviceid', 'name', 'userid', 'lastdate'],
                [
                    [1, 'id1', 'name1', 'user1', '2025-03-15T17:11:14'],
                    [2, 'id2', 'name2', 'user2', $inventoryDate],
                    [3, 'id3', 'name3', 'user3', '2025-03-15T17:11:14'],
                    [4, '_SYSTEMGROUP_', 'group1', null, '2025-03-15T17:11:14'],
                    [5, '_SYSTEMGROUP_', 'group2', null, '2025-03-15T17:11:14'],
                    [6, '_SYSTEMGROUP_', 'group3', null, '2025-03-15T17:11:14'],
                ],
            );
            DatabaseConnection::initializeTable(Table::GroupMemberships, ['hardware_id', 'group_id', 'static'], [
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

            $groups = $this->createGroups($connection, $dataProcessor);

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

    public function testSetSearchResultsQuery()
    {
        $group = $this->createStub(Group::class);
        $searchParams = $this->createStub(SearchParams::class);
        $select = $this->createStub(Select::class);

        $search = $this->createMock(Search::class);
        $search->expects($this->once())->method('getQuery')->with($searchParams)->willReturn($select);
        $search->expects($this->never())->method('getClients');

        $groups = $this->createGroupsMock(['setQuery', 'setMembers'], search: $search);
        $groups->expects($this->once())->method('setQuery')->with($group, $select);
        $groups->expects($this->never())->method('setMembers');

        $groups->setSearchResults($group, $searchParams, Membership::Automatic);
    }

    public static function setSearchResultsClientsProvider()
    {
        return [
            [Membership::Manual],
            [Membership::Never],
        ];
    }

    #[DataProvider('setSearchResultsClientsProvider')]
    public function testSetSearchResultsClients(Membership $membershipType)
    {
        $group = $this->createStub(Group::class);
        $searchParams = $this->createStub(SearchParams::class);
        $clients = [$this->createStub(Client::class)];

        $search = $this->createMock(Search::class);
        $search->expects($this->never())->method('getQuery');
        $search->expects($this->once())->method('getClients')->with($searchParams)->willReturn($clients);

        $groups = $this->createGroupsMock(['setQuery', 'setMembers'], search: $search);
        $groups->expects($this->never())->method('setQuery');
        $groups->expects($this->once())->method('setMembers')->with($group, $clients, $membershipType);

        $groups->setSearchResults($group, $searchParams, $membershipType);
    }

    public static function setQueryProvider()
    {
        return [
            [[]],
            [[['columns' => []]]],
        ];
    }

    #[DataProvider('setQueryProvider')]
    public function testSetQuery(array $joins)
    {
        DatabaseConnection::with(function (Connection $connection) use ($joins): void {
            DatabaseConnection::initializeTable(Table::GroupInfo, ['hardware_id', 'request'], [
                [10, 'query_10'],
                [11, 'query_11'],
            ]);

            $query = 'query_new';

            $select = $this->createMock(Select::class);
            $select->method('getRawState')->willReturnMap([
                [Select::COLUMNS, ['id']],
                [Select::JOINS, $joins],
            ]);

            $sql = $this->createMock(Sql::class);
            $sql->method('buildSqlString')->with($select)->willReturn($query);

            $group = Mockery::mock(Group::class);
            $group->shouldReceive('offsetGet')->with('Id')->andReturn(10);
            $group->shouldReceive('offsetSet')->once()->ordered()->with('DynamicMembersSql', $query);
            $group->shouldReceive('update')->once()->ordered()->with(true);

            $groups = $this->createGroups(connection: $connection, sql: $sql);
            $groups->setQuery($group, $select);

            $content = $connection
                ->createQueryBuilder()
                ->select('request')
                ->from(Table::GroupInfo)
                ->orderBy('hardware_id')
                ->fetchFirstColumn();
            $this->assertEquals([$query, 'query_11'], $content);
        });
    }

    public function testSetQueryInvalidQuery()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Expected 1 column, got 2');

        $connection = $this->createMock(Connection::class);
        $connection->expects($this->never())->method('update');

        $group = $this->createMock(Group::class);
        $group->expects($this->never())->method('update');

        $joins = [
            ['columns' => []],
            ['columns' => ['name']],
        ];
        $select = $this->createStub(Select::class);
        $select->method('getRawState')->willReturnMap([
            [Select::COLUMNS, ['id']],
            [Select::JOINS, $joins],
        ]);

        $groups = $this->createGroups(connection: $connection);
        $groups->setQuery($group, $select);
    }

    public static function setMembersProvider()
    {
        return [
            [Membership::Manual, false, [
                [1, 10, 1],
                [2, 10, 1],
                [3, 10, 1],
                [4, 10, 0],
                [5, 10, 1],
                [1, 11, 0],
            ]],
            [Membership::Never, true, [
                [1, 10, 2],
                [2, 10, 2],
                [3, 10, 2],
                [4, 10, 0],
                [5, 10, 2],
                [1, 11, 0],
            ]],
        ];
    }

    #[DataProvider('setMembersProvider')]
    public function testSetMembers(Membership $type, bool $simulateLockFailure, array $expected)
    {
        DatabaseConnection::with(function (Connection $connection) use ($type, $simulateLockFailure, $expected) {
            DatabaseConnection::initializeTable(Table::GroupMemberships, ['hardware_id', 'group_id', 'static'], [
                [1, 10, 0],
                [2, 10, 1],
                [3, 10, 2],
                [4, 10, 0],
                [1, 11, 0],
            ]);

            $client1 = $this->createStub(Client::class);
            $client1->id = 1;

            $client2 = $this->createStub(Client::class);
            $client2->id = 2;

            $client3 = $this->createStub(Client::class);
            $client3->id = 3;

            $client5 = $this->createStub(Client::class);
            $client5->id = 5;

            $clients = [$client1, $client2, $client3, $client5];

            $group = $this->createMock(Group::class);
            $time = $this->createMock(Time::class);
            if ($simulateLockFailure) {
                $group->expects($this->exactly(2))->method('lock')->willReturn(false, true);
                $time->expects($this->once())->method('sleep')->with(1);
            } else {
                $group->expects($this->once())->method('lock')->willReturn(true);
                $time->expects($this->never())->method('sleep');
            }
            $group->expects($this->once())->method('unlock');
            $group->method('__get')->with('id')->willReturn(10);

            $groups = $this->createGroups($connection, time: $time);
            $groups->setMembers($group, $clients, $type);

            $content = $connection
                ->createQueryBuilder()
                ->select('hardware_id', 'group_id', 'static')
                ->from(Table::GroupMemberships)
                ->addOrderBy('group_id')
                ->addOrderBy('hardware_id')
                ->fetchAllNumeric();
            $this->assertEquals($expected, $content);
        });
    }

    public function testSetMembersExceptionInTransaction()
    {
        $this->expectExceptionMessage('test');

        DatabaseConnection::with(function (): void {
            // Flush table to guarantee that inseert() will be called instead of update()
            DatabaseConnection::initializeTable(Table::GroupMemberships, [], []);

            $connection = $this->createMock(Connection::class);
            $connection->method('createQueryBuilder')->willReturn($connection->createQueryBuilder());
            $connection->expects($this->once())->method('beginTransaction');
            $connection->expects($this->once())->method('rollBack');
            $connection->expects($this->never())->method('commit');
            $connection->method('insert')->willThrowException(new Exception('test'));

            $group = $this->createMock(Group::class);
            $group->expects($this->once())->method('lock')->willReturn(true);
            $group->expects($this->once())->method('unlock');
            $group->method('__get')->with('id')->willReturn(10);

            $client = $this->createStub(Client::class);
            $client->id = 1;

            $groups = $this->createGroups($connection);
            $groups->setMembers($group, [$client], Membership::Manual);
        });
    }
}
