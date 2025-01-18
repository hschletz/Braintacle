<?php

namespace Braintacle\Duplicates;

/**
 * Request parameters for allowing duplicates.
 */
class AllowDuplicatesRequestParameters
{
    public Criterion $criterion;
    public string $value;
}
