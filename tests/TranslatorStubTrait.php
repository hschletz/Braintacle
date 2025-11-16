<?php

namespace Braintacle\Test;

use Laminas\Translator\TranslatorInterface;

trait TranslatorStubTrait
{
    private function createTranslatorStub(): TranslatorInterface
    {
        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('translate')->willReturnCallback(fn(string $message) => '_' . $message);

        return $translator;
    }
}
