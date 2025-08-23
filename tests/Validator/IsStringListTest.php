<?php

namespace Braintacle\Test\Validator;

use Braintacle\Validator\IsStringList;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\TestCase;

class IsStringListTest extends TestCase
{
    public function testNotList()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Input array is not a list');

        $validator = new IsStringList();
        $validator->validate(['foo' => 'bar']);
    }

    public function testNotString()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Input array contains non-string elements');

        $validator = new IsStringList();
        $validator->validate(['foo', 42, 'bar']);
    }

    #[DoesNotPerformAssertions]
    public function testStringList()
    {
        $validator = new IsStringList();
        $validator->validate(['foo', 'bar']);
    }

    #[DoesNotPerformAssertions]
    public function testEmptyList()
    {
        $validator = new IsStringList();
        $validator->validate([]);
    }
}
