<?php

namespace Braintacle\Test\Transformer;

use Braintacle\Transformer\TrimAndNullify;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class TrimAndNullifyTest extends TestCase
{
    public function testInvalidArgument()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected string, got int');
        $transformer = new TrimAndNullify();
        $transformer->transform(42);
    }

    public function testNonEmptyString()
    {
        $transformer = new TrimAndNullify();
        $this->assertEquals('äää', $transformer->transform('  äää  '));
    }

    public function testZeroString()
    {
        $transformer = new TrimAndNullify();
        $this->assertEquals('0', $transformer->transform('  0  '));
    }

    public function testEmptyString()
    {
        $transformer = new TrimAndNullify();
        $this->assertNull($transformer->transform(''));
    }

    public function testWhitespaceOnly()
    {
        $transformer = new TrimAndNullify();
        $this->assertNull($transformer->transform('  '));
    }
}
