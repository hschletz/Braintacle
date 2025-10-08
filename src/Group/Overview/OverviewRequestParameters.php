<?php

namespace Braintacle\Group\Overview;

use Braintacle\Direction;
use Formotron\Attribute\UseBackingValue;

/**
 * Query parameters for groups overview page.
 */
class OverviewRequestParameters
{
    public OverviewColumn $order = OverviewColumn::Name;

    #[UseBackingValue]
    public Direction $direction = Direction::Ascending;
}
