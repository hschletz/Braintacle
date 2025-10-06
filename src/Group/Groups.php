<?php

namespace Braintacle\Group;

use Braintacle\Database\Table;
use Braintacle\Direction;
use Braintacle\Group\Members\ExcludedClient;
use Braintacle\Group\Members\ExcludedColumn;
use Braintacle\Group\Members\Member;
use Braintacle\Group\Members\MembersColumn;
use Braintacle\Locks;
use Braintacle\Search\Search;
use Braintacle\Search\SearchParams;
use Doctrine\DBAL\Connection;
use Formotron\DataProcessor;
use Laminas\Db\Sql\Select;
use Laminas\Db\Sql\Sql;
use LogicException;
use Model\Client\Client;
use Model\Group\Group;
use Throwable;

/**
 * Manage groups.
 */
final class Groups
{
    private const ColumnClientId = 'id';
    private const ColumnClientFK = 'hardware_id';
    private const ColumnGroupId = 'group_id';
    private const ColumnMembershipType = 'static';


    public function __construct(
        private Connection $connection,
        private DataProcessor $dataProcessor,
        private Locks $locks,
        private Search $search,
        private Sql $sql,
    ) {}

    /**
     * @return iterable<Member>
     */
    public function getMembers(Group $group, MembersColumn $order, Direction $direction)
    {
        $group->update();

        $queryBuilder = $this->connection->createQueryBuilder();
        $expr = $queryBuilder->expr();

        $select = $queryBuilder->select(
            ...array_map(
                fn(MembersColumn $case) => $case->value,
                MembersColumn::cases()
            )
        );
        $select->from(Table::Clients, 'c');
        $select->innerJoin('c', Table::GroupMemberships, 'm', $expr->eq(self::ColumnClientFK, self::ColumnClientId));
        $select->where($expr->eq(self::ColumnGroupId, $queryBuilder->createPositionalParameter($group->id)));
        $select->andWhere(
            $expr->in(
                self::ColumnMembershipType,
                [(string) Membership::Automatic->value, (string) Membership::Manual->value],
            ),
        );
        $select->orderBy($order->value, $direction->value);

        return $this->dataProcessor->iterate($select->executeQuery()->iterateAssociative(), Member::class);
    }

    /**
     * @return iterable<ExcludedClient>
     */
    public function getExcludedClients(Group $group, ExcludedColumn $order, Direction $direction): iterable
    {
        $group->update();

        $queryBuilder = $this->connection->createQueryBuilder();
        $expr = $queryBuilder->expr();

        $select = $queryBuilder->select(
            ...array_map(
                fn(ExcludedColumn $case) => $case->value,
                ExcludedColumn::cases()
            )
        );
        $select->from(Table::Clients, 'c');
        $select->innerJoin('c', Table::GroupMemberships, 'm', $expr->eq(self::ColumnClientFK, self::ColumnClientId));
        $select->where($expr->eq(self::ColumnGroupId, $queryBuilder->createPositionalParameter($group->id)));
        $select->andWhere($expr->eq(self::ColumnMembershipType, Membership::Never->value));
        $select->orderBy($order->value, $direction->value);

        return $this->dataProcessor->iterate($select->executeQuery()->iterateAssociative(), ExcludedClient::class);
    }

    /**
     * Set memberships from search result.
     */
    public function setSearchResults(Group $group, SearchParams $searchParams, Membership $membershipType): void
    {
        if ($membershipType == Membership::Automatic) {
            $this->setQuery($group, $this->search->getQuery($searchParams));
        } else {
            $this->setMembers(
                $group,
                $this->search->getClients($searchParams),
                $membershipType,
            );
        }
    }

    /**
     * Set Query for automatic group membership.
     */
    public function setQuery(Group $group, Select $select): void
    {
        $numColumns = count($select->getRawState(Select::COLUMNS));
        foreach ($select->getRawState(Select::JOINS) as $join) {
            $numColumns += count($join['columns']);
        }
        if ($numColumns != 1) {
            throw new LogicException('Expected 1 column, got ' . $numColumns);
        }

        $sql = $this->sql->buildSqlString($select);

        $this->connection->update(
            Table::GroupInfo,
            ['request' => $sql],
            ['hardware_id' => $group->id]
        );

        $group->dynamicMembersSql = $sql;
        $group->update(force: true); // Force cache update, effectively validating query
    }

    /**
     * Set manually included/excluded members.
     *
     * @param iterable<Client> $clients
     */
    public function setMembers(Group $group, iterable $clients, Membership $membershipType): void
    {
        if (!$this->locks->lock($group)) {
            // Another request is either updating memberships or deleting the
            // group. Proceeding would be pointless.
            return;
        }

        $groupId = $group->id;
        $membership = $membershipType->value;
        try {
            $existingMemberships = $this->connection
                ->createQueryBuilder()
                ->select('hardware_id', 'static')
                ->from(Table::GroupMemberships)
                ->where('group_id = :id')
                ->setParameter('id', $groupId)
                ->fetchAllKeyValue();

            $this->connection->beginTransaction();
            try {
                foreach ($clients as $client) {
                    $clientId = $client->id;
                    if (isset($existingMemberships[$clientId])) {
                        // Update only memberships of a different type
                        if ($existingMemberships[$clientId] != $membership) {
                            $this->connection->update(
                                Table::GroupMemberships,
                                ['static' => $membership],
                                ['group_id' => $groupId, 'hardware_id' => $clientId],
                            );
                        }
                    } else {
                        $this->connection->insert(
                            Table::GroupMemberships,
                            ['group_id' => $groupId, 'hardware_id' => $clientId, 'static' => $membership],
                        );
                    }
                }
                $this->connection->commit();
            } catch (Throwable $throwable) {
                $this->connection->rollBack();
                throw $throwable;
            }
        } finally {
            $this->locks->release($group);
        }
    }
}
