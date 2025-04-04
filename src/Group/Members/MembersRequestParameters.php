<?php

namespace Braintacle\Group\Members;

use Braintacle\Direction;
use Braintacle\Group\GroupRequestParameters;
use Formotron\Attribute\UseBackingValue;

/**
 * Query parameters for members page.
 */
class MembersRequestParameters extends GroupRequestParameters
{
    public MembersColumn $order = MembersColumn::InventoryDate;

    #[UseBackingValue]
    public Direction $direction = Direction::Descending;
}
