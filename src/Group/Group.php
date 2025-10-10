<?php

namespace Braintacle\Group;

use Braintacle\KeyMapper\CamelCaseToSnakeCase;
use Braintacle\Transformer\DateTime;
use DateTimeInterface;
use Formotron\Attribute\MapKeys;

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
#[MapKeys(CamelCaseToSnakeCase::class)]
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
    #[DateTime(DateTime::Database)]
    public DateTimeInterface $creationDate;

    /**
     * SQL query for dynamic members, may be empty.
     */
    public ?string $dynamicMembersSql;

    /**
     * Timestamp of last cache update.
     */
    #[DateTime(DateTime::Epoch)]
    public ?DateTimeInterface $cacheCreationDate;

    /**
     * Timestamp when cache will expire and get rebuilt.
     */
    #[DateTime(DateTime::Epoch)]
    public ?DateTimeInterface $cacheExpirationDate;
}
