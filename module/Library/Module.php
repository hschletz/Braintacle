<?php
/**
 * The Library module
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

namespace Library;

use Zend\ModuleManager\Feature;

/**
 * The Library module
 * 
 * This module provides a library of general purpose classes (not specific to
 * other modules). Provided view helpers etc. are automatically registered and
 * don't need to be loaded explicitly.
 *
 * @codeCoverageIgnore
 */
class Module implements
Feature\AutoloaderProviderInterface,
Feature\ConfigProviderInterface,
Feature\InitProviderInterface
{
    /**
     * @internal
     */
    public function init(\Zend\ModuleManager\ModuleManagerInterface $manager)
    {
        $manager->loadModule('Database');
    }

    /**
     * @internal
     */
    public function getConfig()
    {
        $config = array(
            'controller_plugins' => array(
                'invokables' => array(
                    '_' => 'Library\Mvc\Controller\Plugin\TranslationHelper',
                    'RedirectToRoute' => 'Library\Mvc\Controller\Plugin\RedirectToRoute',
                    'UrlFromRoute' => 'Library\Mvc\Controller\Plugin\UrlFromRoute',
                )
            ),
            'service_manager' => array(
                'aliases' => array(
                    'Zend\Authentication\AuthenticationService' => 'Library\AuthenticationService',
                ),
                'factories' => array(
                    '\Library\Logger' => 'Library\Log\LoggerServiceFactory',
                ),
                'invokables' => array(
                    'Library\ArchiveManager' => 'Library\ArchiveManager',
                    'Library\AuthenticationService' => '\Library\Authentication\AuthenticationService',
                    'Library\Now' => 'DateTime',
                    'Library\Random' => 'Library\Random',
                ),
                'shared' => array(
                    'Library\Now' => false,
                ),
            ),
            'translator_plugins' => array(
                'invokables' => array(
                    'Po' => 'Library\I18n\Translator\Loader\Po',
                )
            ),
            'view_helpers' => array(
                'factories' => array(
                    'formYesNo' => 'Library\View\Helper\Service\FormYesNoFactory',
                    'htmlTag' => 'Library\View\Helper\Service\HtmlTagFactory',
                ),
                'invokables' => array(
                    'formSelectSimple' => 'Library\View\Helper\FormSelectSimple',
                    'formSelectUntranslated' => 'Library\View\Helper\FormSelectUntranslated',
                ),
            ),
        );
        $config += Application::getTranslationConfig(static::getPath('data/i18n'));

        if (\Locale::getPrimaryLanguage(\Locale::getDefault()) != 'en') {
            $zfTranslations = @$appConfig = Application::getConfig()['paths']['Zend translations'];
            if (is_dir($zfTranslations)) {
                $locale = \Locale::getDefault();
                $translationFile = "$zfTranslations/$locale/Zend_Validate.php";
                if (!is_file($translationFile)) {
                    $locale = \Locale::getPrimaryLanguage($locale);
                    $translationFile = "$zfTranslations/$locale/Zend_Validate.php";
                    if (!is_file($translationFile)) {
                        $translationFile = null;
                    }
                }
                if ($translationFile) {
                    $config['translator']['translation_files'][] = array(
                        'type' => 'phparray',
                        'filename' => $translationFile,
                        'text_domain' => 'Zend',
                    );
                }
            }
        }

        return $config;
    }

    /**
     * @internal
     */
    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__,
                ),
            ),
        );
    }

    /**
     * @internal
     */
    public function onBootstrap(\Zend\EventManager\EventInterface $e)
    {
        \Zend\Filter\StaticFilter::getPluginManager()->setInvokableClass(
            'Library\FixEncodingErrors',
            'Library\Filter\FixEncodingErrors'
        );
        $serviceManager = $e->getApplication()->getServiceManager();

        // Register form element view helpers
        $formElementHelper = $serviceManager->get('ViewHelperManager')->get('formElement');
        $formElementHelper->addClass('Library\Form\Element\SelectSimple', 'formselectsimple');
        $formElementHelper->addType('select_untranslated', 'formselectuntranslated');

        if (\Locale::getPrimaryLanguage(\Locale::getDefault()) != 'en') {
            $mvcTranslator = $serviceManager->get('MvcTranslator');
            if (Application::isDevelopment()) {
                $translator = $mvcTranslator->getTranslator();
                $translator->enableEventManager();
                $translator->getEventManager()->attach(
                    \Zend\I18n\Translator\Translator::EVENT_MISSING_TRANSLATION,
                    array($this, 'onMissingTranslation')
                );
            }
            // Validators have no translator by default. Attach translator, but
            // use a different text domain to avoid warnings if the Zend
            // translations are not loaded. For custom messages, the text domain
            // must be reset manually to 'default' for individual validators.
            \Zend\Validator\AbstractValidator::setDefaultTranslator($mvcTranslator);
            \Zend\Validator\AbstractValidator::setDefaultTranslatorTextDomain('Zend');
        }
    }

    /**
     * Event handler for missing translations
     * @param \Zend\EventManager\EventInterface $e
     * @internal
     */
    public function onMissingTranslation(\Zend\EventManager\EventInterface $e)
    {
        // Issue warning about missing translation for the 'default' text
        // domain. This warning will indicate either a message string missing in
        // the translation file, or accidental translator invokation when a
        // string should not actually be translated.
        if ($e->getParam('text_domain') == 'default') {
            trigger_error('Missing translation: ' . $e->getParam('message'), E_USER_NOTICE);
        }
    }

    /**
     * Get path to module directory
     *
     * @param string $path Optional path component that is appended to the module root path
     * @return string Absolute path to requested file/directory (directories without trailing slash)
     */
    static function getPath($path='')
    {
        return \Library\Application::getPath('module/Library/' . $path);
    }
}
