<?php

namespace Braintacle\Test\Validator;

use AssertionError;
use Braintacle\Validator\AssertNumericRange;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\TestCase;

#[CoversClass(AssertNumericRange::class)]
final class AssertNumericRangeTest extends TestCase
{
    public function testNoArgumentsInvalid()
    {
        $this->expectException(AssertionError::class);
        $validator = new AssertNumericRange();
        $validator->validate(1);
    }

    public function testMinGreaterThanMaxInvalid()
    {
        $this->expectException(AssertionError::class);
        $validator = new AssertNumericRange(min: 2, max: 1);
        $validator->validate(1);
    }

    public function testMinEqualsMaxInvalid()
    {
        $this->expectException(AssertionError::class);
        $validator = new AssertNumericRange(min: 2, max: 2);
        $validator->validate(2);
    }

    public static function invalidTypeProvider()
    {
        return [
            [null],
            [false],
            ['1'],
            [1.5],
        ];
    }

    #[DataProvider('invalidTypeProvider')]
    public function testInvalidType(mixed $value)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected int, got ' . gettype($value));
        $validator = new AssertNumericRange(1);
        $validator->validate($value);
    }

    #[DoesNotPerformAssertions]
    public function testNumberValidAtLowerBound()
    {
        $validator = new AssertNumericRange(min: 2, max: 3);
        $validator->validate(2);
    }

    #[DoesNotPerformAssertions]
    public function testNumberValidAtUpperBound()
    {
        $validator = new AssertNumericRange(min: 2, max: 3);
        $validator->validate(3);
    }

    #[DoesNotPerformAssertions]
    public function testValidNoLowerBound()
    {
        $validator = new AssertNumericRange(max: 3);
        $validator->validate(2);
    }

    #[DoesNotPerformAssertions]
    public function testValidNoUpperBound()
    {
        $validator = new AssertNumericRange(min: 2);
        $validator->validate(3);
    }

    public function testNumberTooSmall()
    {
        $this->expectException(InvalidArgumentException::class);
        $validator = new AssertNumericRange(min: 2, max: 3);
        $validator->validate(1);
    }

    public function testNumberTooLarge()
    {
        $this->expectException(InvalidArgumentException::class);
        $validator = new AssertNumericRange(min: 2, max: 3);
        $validator->validate(4);
    }
}
