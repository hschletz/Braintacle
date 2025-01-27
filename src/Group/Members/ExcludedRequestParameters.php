<?php

namespace Braintacle\Group\Members;

use Braintacle\Direction;
use Braintacle\Group\GroupRequestParameters;

/**
 * Query parameters for "excluded" page.
 */
class ExcludedRequestParameters extends GroupRequestParameters
{
    public ExcludedColumn $order = ExcludedColumn::InventoryDate;
    public Direction $direction = Direction::Descending;
}
