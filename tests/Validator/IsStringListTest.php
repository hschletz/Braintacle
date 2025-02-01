<?php

namespace Braintacle\Test\Validator;

use Braintacle\Validator\IsStringList;
use PHPUnit\Framework\TestCase;

class IsStringListTest extends TestCase
{
    public function testNotList()
    {
        $validator = new IsStringList();
        $this->assertEquals(
            ['Input array is not a list'],
            $validator->getValidationErrors(['foo' => 'bar'], [])
        );
    }

    public function testNotString()
    {
        $validator = new IsStringList();
        $this->assertEquals(
            ['Input array contains non-string elements'],
            $validator->getValidationErrors(['foo', 42, 'bar'], [])
        );
    }

    public function testStringList()
    {
        $validator = new IsStringList();
        $this->assertEquals(
            [],
            $validator->getValidationErrors(['foo', 'bar'], [])
        );
    }

    public function testEmptyList()
    {
        $validator = new IsStringList();
        $this->assertEquals(
            [],
            $validator->getValidationErrors([], [])
        );
    }
}
