<?php

namespace Braintacle\Software;

/**
 * Actions for software management.
 */
enum Action: string
{
    case Accept = 'accept';
    case Ignore = 'ignore';
}
