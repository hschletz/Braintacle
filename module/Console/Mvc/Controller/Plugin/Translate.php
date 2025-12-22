<?php

/**
 * Translate string.
 */

namespace Console\Mvc\Controller\Plugin;

use Braintacle\Legacy\Plugin\ControllerPluginTrait;
use Laminas\Translator\TranslatorInterface;

/**
 * Translate string.
 */
final class Translate
{
    use ControllerPluginTrait;

    public function __construct(private TranslatorInterface $translator) {}

    public function __invoke(string $message): string
    {
        return $this->translator->translate($message);
    }
}
