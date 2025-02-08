<?php

namespace Braintacle\Test\Validator;

use Braintacle\Test\DataProcessorTestTrait;
use Braintacle\Validator\AssertStringLength;
use Braintacle\Validator\StringLengthValidator;
use PHPUnit\Framework\TestCase;

class AssertStringLengthTest extends TestCase
{
    use DataProcessorTestTrait;

    public function testExplicitMax()
    {
        $validator = $this->createMock(StringLengthValidator::class);
        $validator->method('getValidationErrors')->with($this->anything(), ['min' => 0, 'max' => 1])->willReturn([]);

        $dataObject = new class
        {
            #[AssertStringLength(min: 0, max: 1)]
            public string $value;
        };
        $result = $this->processData(['value' => 'str'], get_class($dataObject), [StringLengthValidator::class => $validator]);
        $this->assertEquals('str', $result->value);
    }

    public function testDefaultMax()
    {
        $validator = $this->createMock(StringLengthValidator::class);
        $validator->method('getValidationErrors')->with($this->anything(), ['min' => 1, 'max' => null])->willReturn([]);

        $dataObject = new class
        {
            #[AssertStringLength(min: 1)]
            public string $value;
        };
        $result = $this->processData(['value' => 'str'], get_class($dataObject), [StringLengthValidator::class => $validator]);
        $this->assertEquals('str', $result->value);
    }
}
