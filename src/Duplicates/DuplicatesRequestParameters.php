<?php

namespace Braintacle\Duplicates;

use Braintacle\Direction;

/**
 * Request parameters for duplicates table.
 */
class DuplicatesRequestParameters
{
    public Criterion $criterion;
    public DuplicatesColumn $order = DuplicatesColumn::Id;
    public Direction $direction = Direction::Ascending;
}
