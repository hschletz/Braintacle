<?php

namespace Braintacle\Test\Transformer;

use AssertionError;
use Braintacle\Transformer\DateTimeTransformer;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DateTimeTransformerTest extends TestCase
{
    public static function invalidArgumentCountProvider()
    {
        return [
            [[]],
            [[null]],
            [[null, null, null]],
        ];
    }

    #[DataProvider('invalidArgumentCountProvider')]
    public function testInvalidArgumentCount(array $args)
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->never())->method('getDatabasePlatform');

        $this->expectException(AssertionError::class);
        $this->expectExceptionMessage('Expected 2 arguments');
        $transformer = new DateTimeTransformer($connection);
        $transformer->transform(null, $args);
    }

    public function testArgsNotList()
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->never())->method('getDatabasePlatform');

        $this->expectException(AssertionError::class);
        $this->expectExceptionMessage('Expected arguments as list');
        $transformer = new DateTimeTransformer($connection);
        $transformer->transform(null, ['foo' => null, 'bar' => null]);
    }

    public function testFormatNotString()
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->never())->method('getDatabasePlatform');

        $this->expectException(AssertionError::class);
        $transformer = new DateTimeTransformer($connection);

        /** @psalm-suppress InvalidArgument (intentionally invalid) */
        $transformer->transform(null, [1, null]);
    }

    public function testInvalidValue()
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->never())->method('getDatabasePlatform');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Value 'invalid' cannot be parsed with format 'Y-m-d'");
        $transformer = new DateTimeTransformer($connection);
        $transformer->transform('invalid', ['Y-m-d', null]);
    }

    public function testExplicitFormat()
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->never())->method('getDatabasePlatform');

        $timestamp = '2025-03-10T17:35:01+01:00';
        $transformer = new DateTimeTransformer($connection);
        $this->assertEquals(
            new DateTimeImmutable($timestamp),
            $transformer->transform($timestamp, [DateTimeInterface::ATOM, null]),
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
            $transformer->transform('10.03.2025 17:35:01', [null, null]),
        );
    }

    public function testNullValue()
    {
        $connection = $this->createStub(Connection::class);
        $transformer = new DateTimeTransformer($connection);
        $this->assertNull($transformer->transform(null, [null, null]));
    }

    public function testZeroEpochBecomesNull()
    {
        $connection = $this->createStub(Connection::class);
        $transformer = new DateTimeTransformer($connection);
        $this->assertNull($transformer->transform(0, ['U', null]));
    }

    public function testInvalidTimezone()
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->never())->method('getDatabasePlatform');

        $this->expectException(AssertionError::class);
        $this->expectExceptionMessage('not a DateTimeZone object');
        $transformer = new DateTimeTransformer($connection);

        /** @psalm-suppress InvalidArgument (intentionally invalid) */
        $transformer->transform(null, [null, 'UTC']);
    }

    public function testDefaultTimezone()
    {
        $connection = $this->createStub(Connection::class);
        $transformer = new DateTimeTransformer($connection);
        $this->assertEquals(
            date_default_timezone_get(),
            $transformer->transform('2025-03-10 17:35:01', ['Y-m-d H:i:s', null])->getTimezone()->getName()
        );
    }

    public static function explicitTimezoneProvider()
    {
        return [
            ['UTC'],
            ['Europe/Berlin'],
        ];
    }

    #[DataProvider('explicitTimezoneProvider')]
    public function testExplicitTimezone(string $timezone)
    {
        $connection = $this->createStub(Connection::class);
        $transformer = new DateTimeTransformer($connection);
        $timestamp = $transformer->transform('2025-03-10 17:35:01', ['Y-m-d H:i:s', new DateTimeZone($timezone)]);
        $this->assertEquals($timezone, $timestamp->getTimezone()->getName());
    }
}
