<?php

namespace Console\Template\Functions;

use Laminas\I18n\Translator\TranslatorInterface;

/**
 * Translate message, optionally replace sprintf() placeholders with extra arguments.
 */
class TranslateFunction
{
    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function __invoke(string $message, ...$args): string
    {
        return vsprintf($this->translator->translate($message), $args);
    }
}
