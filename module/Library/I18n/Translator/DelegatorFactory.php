<?php

/**
 * Delegator for Translator initialization
 *
 * Copyright (C) 2011-2022 Holger Schletz <holger.schletz@web.de>
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option)
 * any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for
 * more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

namespace Library\I18n\Translator;

/**
 * Delegator for Translator initialization
 */
class DelegatorFactory implements \Laminas\ServiceManager\Factory\DelegatorFactoryInterface
{
    /** {@inheritdoc} */
    public function __invoke(
        \Interop\Container\ContainerInterface $container,
        $name,
        callable $callback,
        array $options = null
    ) {
        $locale = \Locale::getDefault();
        $primaryLanguage = \Locale::getPrimaryLanguage($locale);

        $mvcTranslator = $callback();
        $mvcTranslator->setPluginManager($container->get('TranslatorPluginManager'));
        $translator = $mvcTranslator->getTranslator();

        // Set language
        $translator->setLocale($locale);
        if ($primaryLanguage != $locale) {
            // Set primary language as fallback, i.e. "de" if locale is "de_DE".
            // This enables translation file patterns for "de" to match all
            // variants.
            $translator->setFallbackLocale($primaryLanguage);
        }

        // Load translations for Laminas validator messages
        $translator->addTranslationFilePattern(
            'phparray',
            \Laminas\I18n\Translator\Resources::getBasePath(),
            \Laminas\I18n\Translator\Resources::getPatternForValidator()
        );

        // Set up event listener for missing translations
        if ($primaryLanguage != 'en') {
            $config = $container->get('Library\UserConfig');
            if (@$config['debug']['report missing translations']) {
                $translator->enableEventManager();
                $translator->getEventManager()->attach(
                    \Laminas\I18n\Translator\Translator::EVENT_MISSING_TRANSLATION,
                    array($this, 'onMissingTranslation')
                );
            }
        }

        return $mvcTranslator;
    }

    /**
     * Event handler for missing translations
     *
     * @param \Laminas\EventManager\EventInterface $e
     */
    public function onMissingTranslation(\Laminas\EventManager\EventInterface $e)
    {
        // Issue warning about missing translation for the 'default' text
        // domain. This warning will indicate either a message string missing in
        // the translation file, or accidental translator invokation when a
        // string should not actually be translated.
        // If a fallback locale is involved, suppress the warning for the
        // standard locale.
        $fallbackLocale = $e->getTarget()->getFallbackLocale();
        if (
            (!$fallbackLocale or $e->getParam('locale') == $fallbackLocale) and
            $e->getParam('text_domain') == 'default'
        ) {
            trigger_error('Missing translation: ' . $e->getParam('message'), E_USER_NOTICE);
            // @codeCoverageIgnoreStart
        }
        // @codeCoverageIgnoreEnd
    }
}
