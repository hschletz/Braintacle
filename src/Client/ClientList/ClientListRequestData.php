<?php

namespace Braintacle\Client\ClientList;

use Braintacle\Direction;
use Formotron\Attribute\UseBackingValue;

/**
 * Table sorting parameters.
 */
final class ClientListRequestData
{
    public ClientListColumn $order = ClientListColumn::InventoryDate;

    #[UseBackingValue]
    public Direction $direction = Direction::Descending;
}
