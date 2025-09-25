<?php

namespace Braintacle\Test;

use Braintacle\Time;
use PHPUnit\Framework\TestCase;

class TimeTest extends TestCase
{
    public function testNow()
    {
        $time = new Time();
        $diff = time() - $time->now()->getTimestamp();
        $this->assertGreaterThanOrEqual(0, $diff);
        $this->assertLessThanOrEqual(1, $diff); // possible turnaround between calls
    }
}
