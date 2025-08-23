<?php

namespace Braintacle\Test\Transformer;

use AssertionError;
use Braintacle\Validator\AssertStringLength;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\TestCase;

class AssertStringLengthTest extends TestCase
{
    public function testMinNegative()
    {
        $this->expectException(AssertionError::class);
        new AssertStringLength(min: -1, max: 2);
    }

    public function testMaxLessThenOne()
    {
        $this->expectException(AssertionError::class);
        new AssertStringLength(min: 0, max: 0);
    }

    public function testMaxLessThanMin()
    {
        $this->expectException(AssertionError::class);
        new AssertStringLength(min: 2, max: 1);
    }

    public function testNullValueWithMinGreaterThanZero()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected string, got NULL');
        $validator = new AssertStringLength(min: 1, max: 2);
        $validator->validate(null);
    }

    public function testStringTooShort()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('String length 1 is lower than 2');
        $validator = new AssertStringLength(min: 2, max: 2);
        $validator->validate('ä');
    }

    public function testStringTooLong()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('String length 2 is higher than 1');
        $validator = new AssertStringLength(min: 1, max: 1);
        $validator->validate('ää');
    }

    public static function validArgumentsProvider()
    {
        return [
            [null, 0, 1],
            ['', 0, null],
            ['', 0, 1],
            ['ä', 0, 1],
            ['ä', 1, 1],
            ['ä', 1, null],
        ];
    }

    #[DataProvider('validArgumentsProvider')]
    #[DoesNotPerformAssertions]
    public function testValidArguments(?string $value, int $min, ?int $max)
    {
        $validator = new AssertStringLength($min, $max);
        $validator->validate($value);
    }
}
