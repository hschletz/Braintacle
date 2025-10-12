<?php

namespace Braintacle\Test\Transformer;

use AssertionError;
use Braintacle\Transformer\DateTimeTransformer;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class DateTimeTransformerTest extends TestCase
{
    public function testTooManyArgs()
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->never())->method('getDatabasePlatform');

        $this->expectException(AssertionError::class);
        $transformer = new DateTimeTransformer($connection);
        $transformer->transform(null, ['arg1', 'arg2']);
    }

    public function testArgNotString()
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->never())->method('getDatabasePlatform');

        $this->expectException(AssertionError::class);
        $transformer = new DateTimeTransformer($connection);
        $transformer->transform(null, [1]);
    }

    public function testInvalidValue()
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->never())->method('getDatabasePlatform');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Value 'invalid' cannot be parsed with format 'Y-m-d'");
        $transformer = new DateTimeTransformer($connection);
        $transformer->transform('invalid', ['Y-m-d']);
    }

    public function testExplicitFormat()
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->never())->method('getDatabasePlatform');

        $timestamp = '2025-03-10T17:35:01+01:00';
        $transformer = new DateTimeTransformer($connection);
        $this->assertEquals(
            new DateTimeImmutable($timestamp),
            $transformer->transform($timestamp, [DateTimeInterface::ATOM]),
        );
    }

    public function testDefaultFormat()
    {
        $platform = $this->createStub(AbstractPlatform::class);
        $platform->method('getDateTimeFormatString')->willReturn('d.m.Y H:i:s');

        $connection = $this->createMock(Connection::class);
        $connection->method('getDatabasePlatform')->willReturn($platform);

        $transformer = new DateTimeTransformer($connection);
        $this->assertEquals(
            new DateTimeImmutable('2025-03-10T17:35:01'),
            $transformer->transform('10.03.2025 17:35:01', []),
        );
    }

    public function testNullValue()
    {
        $connection = $this->createStub(Connection::class);
        $transformer = new DateTimeTransformer($connection);
        $this->assertNull($transformer->transform(null, []));
    }

    public function testZeroEpochBecomesNull()
    {
        $connection = $this->createStub(Connection::class);
        $transformer = new DateTimeTransformer($connection);
        $this->assertNull($transformer->transform(0, ['U']));
    }
}
