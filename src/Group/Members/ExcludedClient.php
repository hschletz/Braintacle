<?php

namespace Braintacle\Group\Members;

use Braintacle\Transformer\DateTime;
use DateTimeInterface;
use Formotron\Attribute\Key;

final class ExcludedClient
{
    public int $id;

    public string $name;

    #[Key('userid')]
    public string $userName;

    #[Key('lastdate')]
    #[DateTime(timezone: 'UTC')]
    public DateTimeInterface $inventoryDate;
}
