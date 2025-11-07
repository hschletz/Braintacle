<?php

namespace Braintacle\Client\ClientList;

use Braintacle\Transformer\DateTime;
use DateTimeInterface;
use Formotron\Attribute\Key;

/**
 * Client row.
 */
final class Client
{
    public int $id;

    public string $name;

    #[Key('userid')]
    public string $userName;

    #[Key('osname')]
    public string $osName;

    public ?string $type;

    #[Key('processors')]
    public int $cpuClock;

    #[Key('memory')]
    public int $physicalMemory;

    #[Key('lastdate')]
    #[DateTime(timezone: 'UTC')]
    public DateTimeInterface $inventoryDate;
}
