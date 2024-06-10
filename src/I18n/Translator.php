<?php

namespace Braintacle\I18n;

use Braintacle\AppConfig;
use Gettext\Loader\StrictPoLoader;
use Gettext\Translation;
use Laminas\I18n\Translator\Resources;
use Laminas\I18n\Translator\TranslatorInterface;
use Locale;
use LogicException;
use RuntimeException;

class Translator implements TranslatorInterface
{
    private string $language;

    private bool $translationsLoaded = false;
    private bool $translationsAvailable = false;

    /** @var array<string, string> */
    private array $translations = [];

    public function __construct(string $locale, private string $dir, private AppConfig $appConfig)
    {
        $this->language = Locale::getPrimaryLanguage($locale);
    }

    /** @return array<string, string> */
    public function getTranslations(): array
    {
        if (!$this->translationsLoaded) {
            $loader = new StrictPoLoader();
            $file = "{$this->dir}/{$this->language}.po";
            if (is_file($file)) {
                // Load translations for Laminas validation messages
                $this->translations = require sprintf(
                    Resources::getBasePath() . Resources::getPatternForValidator(),
                    $this->language
                );

                $translations = $loader->loadFile($file);
                /** @var Translation $translation */
                foreach ($translations as $translation) {
                    if (!$translation->getFlags()->has('fuzzy')) {
                        $this->translations[$translation->getOriginal()] = (string) $translation->getTranslation();
                    }
                }

                $this->translationsAvailable = true;
            }
            $this->translationsLoaded = true;
        }

        return $this->translations;
    }

    public function translate($message, $textDomain = 'default', $locale = null)
    {
        $translations = $this->getTranslations();
        if (isset($translations[$message])) {
            return $translations[$message];
        } else {
            if ($this->translationsAvailable && ($this->appConfig->debug['report missing translations'] ?? false)) {
                throw new RuntimeException('Missing translation: ' . $message);
            }

            return $message;
        }
    }

    public function translatePlural($singular, $plural, $number, $textDomain = 'default', $locale = null)
    {
        // This method should never be encountered because we don't use plurals.
        throw new LogicException('translatePlural() is not implemented yet.');
    }
}
