<?php

namespace Braintacle;

use DateTimeImmutable;
use Override;
use Psr\Clock\ClockInterface;

/**
 * Time related functions.
 *
 * These methods are thin wrappers around various time functions. The
 * implementation in a class makes them easy to mock, simplifying tests.
 */
class Time implements ClockInterface
{
    #[Override]
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable();
    }

    /**
     * @codeCoverageIgnore
     */
    public function sleep(int $seconds): void
    {
        sleep($seconds);
    }
}
