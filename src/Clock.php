<?php

namespace Braintacle;

use DateTimeImmutable;
use Psr\Clock\ClockInterface;

/**
 * Simple PSR-20 clock implementation.
 */
class Clock implements ClockInterface
{
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable();
    }
}
