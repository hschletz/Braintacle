<?php

namespace Braintacle\Test\Template\Function;

use Braintacle\Template\Function\OptionFunction;
use Model\Config;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(OptionFunction::class)]
final class OptionFunctionTest extends TestCase
{
    public function testFunction()
    {
        $config = $this->createMock(Config::class);
        $config->method('__get')->with('option')->willReturn('value');

        $function = new OptionFunction($config);
        $this->assertEquals('value', $function('option'));
    }
}
