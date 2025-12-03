<?php

namespace Braintacle\Search;

use Formotron\Attribute\KeyOnly;
use Formotron\Attribute\UseBackingValue;
use Formotron\Attribute\Validate;

/**
 * Search form parameters.
 */
class SearchParams
{
    /**
     * Filter type.
     */
    #[Validate(SearchFilters::class)]
    public string $filter;

    /**
     * Search value.
     */
    public string $search;

    /**
     * Search operator.
     */
    #[UseBackingValue]
    public SearchOperator $operator;

    /**
     * Invert search results, i.e. return clients that don't match search criteria.
     */
    #[KeyOnly]
    public bool $invert;
}
