<?php

/**
 * Translate string.
 */

namespace Console\Mvc\Controller\Plugin;

use Laminas\Mvc\Controller\Plugin\AbstractPlugin;
use Laminas\Mvc\I18n\Translator;

/**
 * Translate string.
 */
class Translate extends AbstractPlugin
{
    private Translator $translator;

    public function __construct(Translator $translator)
    {
        $this->translator = $translator;
    }

    public function __invoke(string $message): string
    {
        return $this->translator->translate($message);
    }
}
