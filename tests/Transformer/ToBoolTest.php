<?php

namespace Braintacle\Test\Transformer;

use Braintacle\Test\DataProcessorTestTrait;
use Braintacle\Transformer\ToBool;
use Braintacle\Transformer\ToBoolTransformer;
use PHPUnit\Framework\TestCase;

class ToBoolTest extends TestCase
{
    use DataProcessorTestTrait;

    public function testAttribute()
    {
        $transformer = $this->createMock(ToBoolTransformer::class);
        $transformer
            ->method('transform')
            ->with($this->anything(), ['trueValue' => 'yes', 'falseValue' => 'no'])
            ->willReturn(true);

        $dataObject = new class
        {
            #[ToBool(trueValue: 'yes', falseValue: 'no')]
            public bool $value;
        };
        $result = $this->processData(
            ['value' => 'yes'],
            get_class($dataObject),
            [ToBoolTransformer::class => $transformer]
        );
        $this->assertTrue($result->value);
    }
}
