<?php

namespace Braintacle\Client\Software;

use Braintacle\Direction;
use Formotron\Attribute\UseBackingValue;

/**
 * Query parameters for software page.
 */
class SoftwareQueryParams
{
    #[UseBackingValue]
    public SoftwareColumn $order = SoftwareColumn::Name;

    #[UseBackingValue]
    public Direction $direction = Direction::Ascending;
}
