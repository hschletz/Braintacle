<?php

namespace Braintacle\Test\Transformer;

use AssertionError;
use Braintacle\Test\DataProcessorTestTrait;
use Braintacle\Transformer\ToBool;
use PHPUnit\Framework\TestCase;
use UnhandledMatchError;

class ToBoolTest extends TestCase
{
    use DataProcessorTestTrait;

    public function testTrueValue()
    {
        $dataObject = new class
        {
            #[ToBool(trueValue: 'yes', falseValue: 'no')]
            public bool $value;
        };
        $result = $this->processData(['value' => 'yes'], get_class($dataObject));
        $this->assertTrue($result->value);
    }

    public function testFalseValue()
    {
        $dataObject = new class
        {
            #[ToBool(trueValue: 'yes', falseValue: 'no')]
            public bool $value;
        };
        $result = $this->processData(['value' => 'no'], get_class($dataObject));
        $this->assertFalse($result->value);
    }

    public function testInvalidValue()
    {
        $dataObject = new class
        {
            #[ToBool(trueValue: 'yes', falseValue: 'no')]
            public bool $value;
        };
        $this->expectException(UnhandledMatchError::class);
        $this->processData(['value' => ''], get_class($dataObject));
    }

    public function testIdenticalArgs()
    {
        $dataObject = new class
        {
            #[ToBool(trueValue: 'yes', falseValue: 'yes')]
            public bool $value;
        };
        $this->expectException(AssertionError::class);
        $this->processData(['value' => 'yes'], get_class($dataObject));
    }
}
