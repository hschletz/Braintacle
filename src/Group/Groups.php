<?php

namespace Braintacle\Group;

use Braintacle\Direction;
use Braintacle\Group\Members\ExcludedClient;
use Braintacle\Group\Members\ExcludedColumn;
use Braintacle\Group\Members\Member;
use Braintacle\Group\Members\MembersColumn;
use Doctrine\DBAL\Connection;
use Formotron\DataProcessor;
use Model\Group\Group;

/**
 * Manage groups.
 */
final class Groups
{
    private const TableClients = 'clients';
    private const TableMemberships = 'groups_cache';
    private const ColumnClientId = 'id';
    private const ColumnClientFK = 'hardware_id';
    private const ColumnGroupId = 'group_id';
    private const ColumnMembershipType = 'static';


    public function __construct(
        private Connection $connection,
        private DataProcessor $dataProcessor,
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
        $select->from(self::TableClients, 'c');
        $select->innerJoin('c', self::TableMemberships, 'm', $expr->eq(self::ColumnClientFK, self::ColumnClientId));
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
        $select->from(self::TableClients, 'c');
        $select->innerJoin('c', self::TableMemberships, 'm', $expr->eq(self::ColumnClientFK, self::ColumnClientId));
        $select->where($expr->eq(self::ColumnGroupId, $queryBuilder->createPositionalParameter($group->id)));
        $select->andWhere($expr->eq(self::ColumnMembershipType, Membership::Never->value));
        $select->orderBy($order->value, $direction->value);

        return $this->dataProcessor->iterate($select->executeQuery()->iterateAssociative(), ExcludedClient::class);
    }
}
