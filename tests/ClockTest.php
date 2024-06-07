<?php

namespace Braintacle\Test;

use Braintacle\Clock;
use PHPUnit\Framework\TestCase;

class ClockTest extends TestCase
{
    public function testNow()
    {
        $clock = new Clock();
        $diff = time() - $clock->now()->getTimestamp();
        $this->assertGreaterThanOrEqual(0, $diff);
        $this->assertLessThanOrEqual(1, $diff); // possible turnaround between calls
    }
}
