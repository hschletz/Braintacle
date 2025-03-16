<?php

namespace Braintacle\Group\Members;

use Braintacle\Transformer\DateTime;
use DateTimeInterface;
use Formotron\Attribute\Key;

final class ExcludedClient
{
    #[Key('id')]
    public int $id;

    #[Key('name')]
    public string $name;

    #[Key('userid')]
    public string $userName;

    #[Key('lastdate')]
    #[DateTime]
    public DateTimeInterface $inventoryDate;
}
