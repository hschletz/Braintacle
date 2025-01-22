<?php

namespace Braintacle\Group\Overview;

use Braintacle\Direction;

/**
 * Query parameters for groups overview page.
 */
class OverviewRequestParameters
{
    public OverviewColumn $order = OverviewColumn::Name;
    public Direction $direction = Direction::Ascending;
}
