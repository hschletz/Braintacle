<?php

namespace Braintacle\Software;

use Braintacle\Direction;

/**
 * Query params for Software page.
 */
class SoftwarePageFormData
{
    public SoftwareFilter $filter = SoftwareFilter::Accepted;
    public SoftwarePageColumn $order = SoftwarePageColumn::Name;
    public Direction $direction = Direction::Ascending;
}
