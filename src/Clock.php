<?php

namespace Braintacle;

use DateTimeImmutable;
use Override;
use Psr\Clock\ClockInterface;

/**
 * Simple PSR-20 clock implementation.
 */
class Clock implements ClockInterface
{
    #[Override]
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable();
    }
}
