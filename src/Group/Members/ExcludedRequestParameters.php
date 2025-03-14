<?php

namespace Braintacle\Group\Members;

use Braintacle\Direction;
use Braintacle\Group\GroupRequestParameters;
use Formotron\Attribute\UseBackingValue;

/**
 * Query parameters for "excluded" page.
 */
class ExcludedRequestParameters extends GroupRequestParameters
{
    public ExcludedColumn $order = ExcludedColumn::InventoryDate;

    #[UseBackingValue]
    public Direction $direction = Direction::Descending;
}
