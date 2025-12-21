<?php

namespace Braintacle\Legacy;

use Laminas\I18n\Translator\TranslatorInterface as I18nTranslatorInterface;
use Laminas\Translator\TranslatorInterface;
use Laminas\Validator\Translator\TranslatorInterface as ValidatorTranslatorInterface;
use Override;

/**
 * Translator wrapper for deprecated I18n interface.
 *
 * This class wraps the regular translator, but implements the deprecated
 * translator interfaces which are still required by some Laminas components.
 * The interfaces could be implemented directly in the translator, but the
 * wrapper allows keeping non-legacy code free of deprecated stuff.
 */
class I18nTranslator implements I18nTranslatorInterface, ValidatorTranslatorInterface
{
    public function __construct(private TranslatorInterface $translator) {}

    #[Override]
    public function translate($message, $textDomain = 'default', $locale = null)
    {
        return $this->translator->translate($message, $textDomain, $locale);
    }

    #[Override]
    public function translatePlural($singular, $plural, $number, $textDomain = 'default', $locale = null)
    {
        return $this->translator->translatePlural($singular, $plural, $number, $textDomain, $locale);
    }
}
