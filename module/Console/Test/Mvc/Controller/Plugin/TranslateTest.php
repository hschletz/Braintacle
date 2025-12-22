<?php

namespace Console\Test\Mvc\Controller\Plugin;

use Console\Mvc\Controller\Plugin\Translate;
use Laminas\Translator\TranslatorInterface;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Translate controller plugin
 */
class TranslateTest extends TestCase
{
    public function testInvoke()
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('translate')->with('input')->willReturn('output');

        $plugin = new Translate($translator);
        $this->assertEquals('output', $plugin('input'));
    }
}
