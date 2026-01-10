<?php

namespace Braintacle\Package\PackageList;

use Braintacle\Direction;
use Formotron\Attribute\UseBackingValue;

/**
 * Table sorting parameters.
 */
final class PackageListRequestParameters
{
    public PackageListColumn $order = PackageListColumn::Name;

    #[UseBackingValue]
    public Direction $direction = Direction::Ascending;
}
