<?php

namespace Braintacle\Legacy;

use Laminas\I18n\Translator\TranslatorInterface as I18nTranslatorInterface;
use Laminas\Translator\TranslatorInterface;

/**
 * Translator wrapper for deprecated I18n interface.
 *
 * This class wraps the regular translator, but implements the deprecated
 * Laminas\I18n\TranslatorInterface which is still required by some Laminas
 * components. The interface could be implemented directly in the translator,
 * but the wrapper allows keeping non-legacy code free of deprecated stuff.
 */
class I18nTranslator implements I18nTranslatorInterface
{
    public function __construct(private TranslatorInterface $translator)
    {
    }

    public function translate($message, $textDomain = 'default', $locale = null)
    {
        return $this->translator->translate($message, $textDomain, $locale);
    }

    public function translatePlural($singular, $plural, $number, $textDomain = 'default', $locale = null)
    {
        return $this->translator->translatePlural($singular, $plural, $number, $textDomain, $locale);
    }
}
