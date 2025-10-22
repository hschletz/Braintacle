<?php

namespace Braintacle\Group\Members;

use Braintacle\Group\Membership;
use Braintacle\Transformer\DateTime;
use DateTimeInterface;
use Formotron\Attribute\Key;
use Formotron\Attribute\UseBackingValue;

/**
 * Group member.
 *
 * @psalm-suppress PossiblyUnusedProperty (referenced in template)
 */
final class Member
{
    public int $id;

    public string $name;

    #[Key('userid')]
    public string $userName;

    #[Key('lastdate')]
    #[DateTime(timezone: 'UTC')]
    public DateTimeInterface $inventoryDate;

    #[Key('static')]
    #[UseBackingValue]
    public Membership $membership;
}
