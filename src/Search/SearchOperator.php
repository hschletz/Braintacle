<?php

namespace Braintacle\Search;

/**
 * Operators for search form.
 */
enum SearchOperator: string
{
    case Pattern = 'like';
    case Equal = 'eq';
    case NotEqual = 'ne';
    case LessThan = 'lt';
    case LessOrEqual = 'le';
    case GreaterThan = 'gt';
    case GreaterOrEqual = 'ge';
}
