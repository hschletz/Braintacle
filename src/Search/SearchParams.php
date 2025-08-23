<?php

namespace Braintacle\Search;

use Braintacle\Transformer\ToBool;
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
    #[Validate(SearchFilterValidator::class)]
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
    #[ToBool(trueValue: '1', falseValue: '0')]
    public bool $invert;
}
