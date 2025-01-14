<?php

namespace Braintacle\Test\Template;

use Braintacle\Template\Function\TranslateFunction;
use Laminas\Translator\TranslatorInterface;
use PHPUnit\Framework\TestCase;

class TranslateFunctionTest extends TestCase
{
    public function testSingleArgument()
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('translate')->with('orig')->willReturn('translated');

        $translateFunction = new TranslateFunction($translator);
        $this->assertEquals('translated', $translateFunction('orig'));
    }

    public function testFormatString()
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('translate')->with('orig %s orig')->willReturn('translated %s translated');

        $translateFunction = new TranslateFunction($translator);
        $this->assertEquals('translated arg translated', $translateFunction('orig %s orig', 'arg'));
    }
}
