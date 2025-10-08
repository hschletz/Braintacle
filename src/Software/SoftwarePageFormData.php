<?php

namespace Braintacle\Software;

use Braintacle\Direction;
use Formotron\Attribute\UseBackingValue;

/**
 * Query params for Software page.
 */
class SoftwarePageFormData
{
    #[UseBackingValue]
    public SoftwareFilter $filter = SoftwareFilter::Accepted;

    #[UseBackingValue]
    public SoftwarePageColumn $order = SoftwarePageColumn::Name;

    #[UseBackingValue]
    public Direction $direction = Direction::Ascending;
}
