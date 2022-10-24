<?php

namespace Console\Test\Mvc\Controller\Plugin;

use Console\Mvc\Controller\Plugin\Translate;
use Laminas\Mvc\I18n\Translator;
use Library\Test\Mvc\Controller\Plugin\AbstractTest;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Tests for Translate controller plugin
 */
class TranslateTest extends AbstractTest
{
    public function testInvoke()
    {
        /** @var MockObject|Translator */
        $translator = $this->createMock(Translator::class);
        $translator->method('translate')->with('input')->willReturn('output');

        $plugin = new Translate($translator);
        $this->assertEquals('output', $plugin('input'));
    }
}
