<?php
/**
 * The Library module
 *
 * Copyright (C) 2011-2014 Holger Schletz <holger.schletz@web.de>
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
                    'Library\AuthenticationService' => '\Library\Authentication\AuthenticationService',
                ),
            ),
            'view_helpers' => array(
                'factories' => array(
                    'formYesNo' => 'Library\View\Helper\Service\FormYesNoFactory',
                    'htmlTag' => 'Library\View\Helper\Service\HtmlTagFactory',
                    'membershipType' => 'Library\View\Helper\Service\MembershipTypeFactory',
                ),
                'invokables' => array(
                    'formSelectSimple' => 'Library\View\Helper\FormSelectSimple',
                ),
            ),
        );
        $config += Application::getTranslationConfig(static::getPath('data/i18n'));
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
        $serviceManager->get('ViewHelperManager')->get('formElement')->addClass(
            'Library\Form\Element\SelectSimple',
            'formselectsimple'
        );
        if (\Locale::getPrimaryLanguage(\Locale::getDefault()) != 'en') {
            $mvcTranslator = $serviceManager->get('MvcTranslator');
            $translator = $mvcTranslator->getTranslator();
            $translator->getPluginManager()->setInvokableClass(
                'Po',
                'Library\I18n\Translator\Loader\Po'
            );
            if (Application::isDevelopment()) {
                $translator->enableEventManager();
                $translator->getEventManager()->attach(
                    \Zend\I18n\Translator\Translator::EVENT_MISSING_TRANSLATION,
                    array($this, 'onMissingTranslation')
                );
            }
            \Zend\Validator\AbstractValidator::setDefaultTranslator($mvcTranslator);
        }
    }

    /**
     * Event handler for missing translations
     * @param \Zend\EventManager\EventInterface $e
     * @internal
     */
    public function onMissingTranslation(\Zend\EventManager\EventInterface $e)
    {
        trigger_error('Missing translation: ' . $e->getParam('message'), E_USER_NOTICE);
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
