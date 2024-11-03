<?php

namespace Braintacle\Test\Legacy;

use Braintacle\Legacy\I18nTranslator;
use Laminas\Translator\TranslatorInterface;
use PHPUnit\Framework\TestCase;

class I18nTranslatorTest extends TestCase
{
    public function testTranslate()
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('translate')->with('message', 'testDomain', 'locale')->willReturn('translated');
        $i18nTranslator = new I18nTranslator($translator);
        $this->assertEquals('translated', $i18nTranslator->translate('message', 'testDomain', 'locale'));
    }

    public function testTranslatePlural()
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('translatePlural')->with('singular', 'plural', 42, 'testDomain', 'locale')->willReturn('translated');
        $i18nTranslator = new I18nTranslator($translator);
        $this->assertEquals('translated', $i18nTranslator->translatePlural('singular', 'plural', 42, 'testDomain', 'locale'));
    }
}
