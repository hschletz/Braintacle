<?php
/**
 * Delegator for Translator initialization
 *
 * Copyright (C) 2011-2015 Holger Schletz <holger.schletz@web.de>
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
class DelegatorFactory implements \Zend\ServiceManager\DelegatorFactoryInterface
{
    /** {@inheritdoc} */
    public function createDelegatorWithName(
        \Zend\ServiceManager\ServiceLocatorInterface $services,
        $name,
        $requestedName,
        $callback
    )
    {
        $locale = \Locale::getDefault();
        $primaryLanguage = \Locale::getPrimaryLanguage($locale);

        $mvcTranslator = $callback();
        $translator = $mvcTranslator->getTranslator();

        // Set language
        $translator->setLocale($locale);
        if ($primaryLanguage != $locale) {
            // Set primary language as fallback, i.e. "de" if locale is "de_DE".
            // This enables translation file patterns for "de" to match all
            // variants.
            $translator->setFallbackLocale($primaryLanguage);
        }

        // Load translations for ZF validator messages if available
        // @codeCoverageIgnoreStart
        if (class_exists('Zend\I18n\Translator\Resources')) {
            // Provided by composer
            $translator->addTranslationFilePattern(
                'phpArray',
                \Zend\I18n\Translator\Resources::getBasePath(),
                \Zend\I18n\Translator\Resources::getPatternForValidator(),
                'Zend'
            );
        } elseif ($primaryLanguage != 'en') {
            // Fall back to manual configuration
            $zfTranslations = @\Library\Application::getConfig()['paths']['Zend translations'];
            if (is_dir($zfTranslations)) {
                $translator->addTranslationFilePattern(
                    'phpArray',
                    $zfTranslations,
                    '%s/Zend_Validate.php',
                    'Zend'
                );
            }
        }
        // @codeCoverageIgnoreEnd

        // Set up event listener for missing translations
        if ($primaryLanguage != 'en' and \Library\Application::isDevelopment()) {
            $translator->enableEventManager();
            $translator->getEventManager()->attach(
                \Zend\I18n\Translator\Translator::EVENT_MISSING_TRANSLATION,
                array($this, 'onMissingTranslation')
            );
        }

        // Validator translator is injected in bootstrap because the delegator
        // is invoked after form validation messages get translated.

        return $mvcTranslator;
    }

    /**
     * Event handler for missing translations
     *
     * @param \Zend\EventManager\EventInterface $e
     */
    public function onMissingTranslation(\Zend\EventManager\EventInterface $e)
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
