<?php

namespace Braintacle\Group\Members;

use Braintacle\Direction;
use Braintacle\Group\GroupRequestParameters;

/**
 * Query parameters for members page.
 */
class MembersRequestParameters extends GroupRequestParameters
{
    public MembersColumn $order = MembersColumn::InventoryDate;
    public Direction $direction = Direction::Descending;
}
