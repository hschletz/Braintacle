<?php

namespace Braintacle\Test\Transformer;

use AssertionError;
use Braintacle\Transformer\ToBoolTransformer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use UnhandledMatchError;

class ToBoolTransformerTest extends TestCase
{
    public static function missingArgsProvider()
    {
        return [
            [[]],
            [['trueValue' => 'yes']],
            [['falseValue' => 'false']],
        ];
    }

    #[DataProvider('missingArgsProvider')]
    public function testMissingArgs($args)
    {
        $this->expectException(AssertionError::class);
        $transformer = new ToBoolTransformer();
        $transformer->transform(null, $args);
    }

    public function testIdenticalArgs()
    {
        $this->expectException(AssertionError::class);
        $transformer = new ToBoolTransformer();
        $transformer->transform(null, ['trueValue' => 1, 'falseValue' => 1]);
    }

    public function testSuccess()
    {
        $transformer = new ToBoolTransformer();
        $this->assertTrue($transformer->transform(1, ['trueValue' => 1, 'falseValue' => 0]));
        $this->assertFalse($transformer->transform(0, ['trueValue' => 1, 'falseValue' => 0]));
    }

    public function testStrictComparison()
    {
        $transformer = new ToBoolTransformer();
        $this->assertTrue($transformer->transform(null, ['trueValue' => null, 'falseValue' => false]));
        $this->assertFalse($transformer->transform(false, ['trueValue' => null, 'falseValue' => false]));

        $this->expectException(UnhandledMatchError::class);
        $this->assertTrue($transformer->transform('1', ['trueValue' => 1, 'falseValue' => 0]));
    }
}
