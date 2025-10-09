<?php

namespace Braintacle\Group;

use DateTimeInterface;

/**
 * A group of clients.
 *
 * Packages and settings assigned to a group apply to all members. Clients can
 * become a member by manual assignment or automatically based on the result of
 * a query. It is also possible to unconditionally exclude a client from a group
 * regardless of query result.
 *
 * @psalm-suppress PossiblyUnusedProperty (referenced in template)
 */
final class Group
{
    /**
     * Primary key.
     */
    public int $id;

    /**
     * Name.
     */
    public string $name;

    /**
     * Description.
     */
    public ?string $description;

    /**
     * Timestamp of group creation.
     */
    public DateTimeInterface $creationDate;

    /**
     * SQL query for dynamic members, may be empty.
     */
    public ?string $dynamicMembersSql;

    /**
     * Timestamp of last cache update.
     */
    public ?DateTimeInterface $cacheCreationDate;

    /**
     * Timestamp when cache will expire and get rebuilt.
     */
    public ?DateTimeInterface $cacheExpirationDate;
}
