<?php

/**
 * Translate string.
 */

namespace Console\Mvc\Controller\Plugin;

use Laminas\Mvc\Controller\Plugin\AbstractPlugin;
use Laminas\Translator\TranslatorInterface;

/**
 * Translate string.
 */
class Translate extends AbstractPlugin
{
    public function __construct(private TranslatorInterface $translator) {}

    public function __invoke(string $message): string
    {
        return $this->translator->translate($message);
    }
}
