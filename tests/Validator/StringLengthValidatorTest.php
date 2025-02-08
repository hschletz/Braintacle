<?php

namespace Braintacle\Test\Transformer;

use AssertionError;
use Braintacle\Validator\StringLengthValidator;
use Formotron\AssertionFailedException;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class StringLengthValidatorTest extends TestCase
{
    public static function missingArgsProvider()
    {
        return [
            [[]],
            [['min' => 0]],
            [['max' => 1]],
        ];
    }

    #[DataProvider('missingArgsProvider')]
    public function testMissingArgs($args)
    {
        $this->expectException(AssertionError::class);
        $validator = new StringLengthValidator();
        $validator->getValidationErrors(null, $args);
    }

    public function testMinInvalidType()
    {
        $this->expectException(AssertionError::class);
        $validator = new StringLengthValidator();
        $validator->getValidationErrors(null, ['min' => '1', 'max' => 2]);
    }

    public function testMinNegative()
    {
        $this->expectException(AssertionError::class);
        $validator = new StringLengthValidator();
        $validator->getValidationErrors(null, ['min' => -1, 'max' => 2]);
    }

    public function testMaxInvalidType()
    {
        $this->expectException(AssertionError::class);
        $validator = new StringLengthValidator();
        $validator->getValidationErrors(null, ['min' => 1, 'max' => '2']);
    }

    public function testMaxLessThanMin()
    {
        $this->expectException(AssertionError::class);
        $validator = new StringLengthValidator();
        $validator->getValidationErrors(null, ['min' => 2, 'max' => 1]);
    }

    public function testNullValueWithMinGreaterThanZero()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected string, got NULL');
        $validator = new StringLengthValidator();
        $validator->getValidationErrors(null, ['min' => 1, 'max' => 2]);
    }

    public function testStringTooShort()
    {
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('String length 1 is lower than 2');
        $validator = new StringLengthValidator();
        $validator->getValidationErrors('ä', ['min' => 2, 'max' => 2]);
    }

    public function testStringTooLong()
    {
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('String length 2 is higher than 1');
        $validator = new StringLengthValidator();
        $validator->getValidationErrors('ää', ['min' => 1, 'max' => 1]);
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
    public function testValidArguments(?string $value, int $min, ?int $max)
    {
        $validator = new StringLengthValidator();
        $this->assertEquals([], $validator->getValidationErrors($value, ['min' => $min, 'max' => $max]));
    }
}
