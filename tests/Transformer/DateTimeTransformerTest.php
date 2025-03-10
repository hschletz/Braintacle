<?php

namespace Braintacle\Test\Transformer;

use AssertionError;
use Braintacle\Transformer\DateTimeTransformer;
use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class DateTimeTransformerTest extends TestCase
{
    public function testMissingArgs()
    {
        $this->expectException(AssertionError::class);
        $transformer = new DateTimeTransformer();
        $transformer->transform(null, []);
    }

    public function testTooManyArgs()
    {
        $this->expectException(AssertionError::class);
        $transformer = new DateTimeTransformer();
        $transformer->transform(null, ['arg1', 'arg2']);
    }

    public function testInvalidArgIndex()
    {
        $this->expectException(AssertionError::class);
        $transformer = new DateTimeTransformer();
        $transformer->transform(null, ['key' => 'format']);
    }

    public function testArgNotString()
    {
        $this->expectException(AssertionError::class);
        $transformer = new DateTimeTransformer();
        $transformer->transform(null, [1]);
    }

    public function testInvalidValue()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Value 'invalid' cannot be parsed with format 'Y-m-d'");
        $transformer = new DateTimeTransformer();
        $transformer->transform('invalid', ['Y-m-d']);
    }

    public function testSuccess()
    {
        $timestamp = '2025-03-10T17:35:01+01:00';
        $transformer = new DateTimeTransformer();
        $this->assertEquals(
            new DateTimeImmutable($timestamp),
            $transformer->transform($timestamp, [DateTimeInterface::ATOM]),
        );
    }
}
