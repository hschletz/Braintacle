<?php

namespace Braintacle\Test;

use Braintacle\Time;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\TestCase;

class TimeTest extends TestCase
{
    private function createTime(?Connection $connection = null): Time
    {
        return new Time($connection ?? $this->createStub(Connection::class));
    }

    public function testNow()
    {
        $time = $this->createTime();
        $diff = time() - $time->now()->getTimestamp();
        $this->assertGreaterThanOrEqual(0, $diff);
        $this->assertLessThanOrEqual(1, $diff); // possible turnaround between calls
    }

    #[DoesNotPerformAssertions]
    public function testGetDatabaseTime()
    {
        DatabaseConnection::with(function (Connection $connection): void {
            $time = $this->createTime($connection);
            $time->getDatabaseTime();
            // No meaningful and portable tests on the result. Just verify that
            // it runs without errors.
        });
    }
}
