<?php

namespace Braintacle\Duplicates;

use Braintacle\Direction;
use Formotron\Attribute\UseBackingValue;

/**
 * Request parameters for duplicates table.
 */
class DuplicatesRequestParameters
{
    #[UseBackingValue]
    public Criterion $criterion;

    public DuplicatesColumn $order = DuplicatesColumn::Id;

    #[UseBackingValue]
    public Direction $direction = Direction::Ascending;
}
