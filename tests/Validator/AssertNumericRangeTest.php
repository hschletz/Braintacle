<?php

namespace Braintacle\Test\Validator;

use AssertionError;
use Braintacle\Validator\AssertIpAddress;
use Braintacle\Validator\AssertNumericRange;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
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

    public function testNotIntInvalid()
    {
        $this->expectException(InvalidArgumentException::class);
        $validator = new AssertNumericRange(1);
        $validator->validate('1');
    }

    #[DoesNotPerformAssertions]
    public function testNullValid()
    {
        $validator = new AssertNumericRange(1);
        $validator->validate(null);
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
