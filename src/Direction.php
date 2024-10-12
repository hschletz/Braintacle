<?php

namespace Braintacle;

/**
 * Ordering directions.
 *
 * Backing values are used for URL query strings and SQL queries.
 */
enum Direction: string
{
    case Ascending = 'asc';
    case Descending = 'desc';
}
