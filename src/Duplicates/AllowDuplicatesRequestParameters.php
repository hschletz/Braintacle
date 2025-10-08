<?php

namespace Braintacle\Duplicates;

use Formotron\Attribute\UseBackingValue;

/**
 * Request parameters for allowing duplicates.
 */
class AllowDuplicatesRequestParameters
{
    #[UseBackingValue]
    public Criterion $criterion;
    public string $value;
}
