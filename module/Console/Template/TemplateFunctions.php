<?php

namespace Console\Template;

use Laminas\I18n\Translator\TranslatorInterface;

/**
 * Functions to be made available within templates.
 */
class TemplateFunctions
{
    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * Translate message, optionally replace sprintf() placeholders with extra arguments.
     */
    public function translate(string $message, ...$args): string
    {
        return vsprintf($this->translator->translate($message), $args);
    }
}
