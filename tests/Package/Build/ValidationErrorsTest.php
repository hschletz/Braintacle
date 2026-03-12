<?php

namespace Braintacle\Test\Package\Build;

use Braintacle\Package\Build\ValidationErrors;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ValidationErrors::class)]
final class ValidationErrorsTest extends TestCase
{
    public function testMessages()
    {
        $errors = new ValidationErrors('name exists', 'warn message invalid', 'postInst message invalid');
        $this->assertEquals('Invalid package data, see exception properties for details', $errors->getMessage());
        $this->assertEquals('name exists', $errors->nameExistsMessage);
        $this->assertEquals('warn message invalid', $errors->warnMessageInvalidMessage);
        $this->assertEquals('postInst message invalid', $errors->postInstMessageInvalidMessage);
    }
}
