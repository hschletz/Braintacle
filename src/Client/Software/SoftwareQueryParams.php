<?php

namespace Braintacle\Client\Software;

use Braintacle\Direction;

/**
 * Query parameters for software page.
 */
class SoftwareQueryParams
{
    public SoftwareColumn $order = SoftwareColumn::Name;
    public Direction $direction = Direction::Ascending;
}
