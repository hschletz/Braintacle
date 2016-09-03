<?php
/**
 * The Library module
 *
 * Copyright (C) 2011-2016 Holger Schletz <holger.schletz@web.de>
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
    Feature\ConfigProviderInterface
{
    /**
     * @internal
     */
    public function getConfig()
    {
        return array(
            'controller_plugins' => array(
                'aliases' => array(
                    'redirectToRoute' => 'RedirectToRoute',
                    'urlFromRoute' => 'UrlFromRoute',
                ),
                'invokables' => array(
                    '_' => 'Library\Mvc\Controller\Plugin\TranslationHelper',
                    'RedirectToRoute' => 'Library\Mvc\Controller\Plugin\RedirectToRoute',
                    'UrlFromRoute' => 'Library\Mvc\Controller\Plugin\UrlFromRoute',
                )
            ),
            'filters' => array(
                'invokables' => array(
                    'Library\FixEncodingErrors' => 'Library\Filter\FixEncodingErrors',
                    'Library\LogLevel' => 'Library\Filter\LogLevel',
                ),
            ),
            'service_manager' => array(
                'delegators' => array(
                    'Zend\Mvc\I18n\Translator' => array('Library\I18n\Translator\DelegatorFactory'),
                ),
                'factories' => array(
                    'Library\InventoryUploader' => 'Library\Service\InventoryUploaderFactory',
                    'Library\Logger' => 'Library\Log\LoggerServiceFactory',
                    'Library\UserConfig' => 'Library\Service\UserConfigFactory',
                ),
                'invokables' => array(
                    'Library\ArchiveManager' => 'Library\ArchiveManager',
                    'Library\I18n\Translator\DelegatorFactory' => 'Library\I18n\Translator\DelegatorFactory',
                    'Library\Now' => 'DateTime',
                    'Library\Random' => 'Library\Random',
                ),
                'shared' => array(
                    'Library\Now' => false,
                ),
            ),
            'translator' => array(
                'translation_file_patterns' => array(
                    array(
                        'type' => 'Po',
                        'base_dir' => __DIR__ . '/data/i18n',
                        'pattern' => '%s.po',
                    ),
                ),
            ),
            'translator_plugins' => array(
                'invokables' => array(
                    'Po' => 'Library\I18n\Translator\Loader\Po',
                )
            ),
            'validators' => array(
                'invokables' => array(
                    'Library\DirectoryWritable' => 'Library\Validator\DirectoryWritable',
                    'Library\FileReadable' => 'Library\Validator\FileReadable',
                    'Library\LogLevel' => 'Library\Validator\LogLevel',
                    'Library\NotInArray' => 'Library\Validator\NotInArray',
                    'Library\ProductKey' => 'Library\Validator\ProductKey',
                ),
            ),
            'view_helpers' => array(
                'aliases' => array(
                    'formSelectSimple' => 'FormSelectSimple',
                    'formSelectUntranslated' => 'FormSelectUntranslated',
                    'formYesNo' => 'FormYesNo',
                    'htmlElement' => 'HtmlElement',
                ),
                'factories' => array(
                    'FormYesNo' => 'Library\View\Helper\Service\FormYesNoFactory',
                ),
                'invokables' => array(
                    'FormSelectSimple' => 'Library\View\Helper\FormSelectSimple',
                    'FormSelectUntranslated' => 'Library\View\Helper\FormSelectUntranslated',
                    'HtmlElement' => 'Library\View\Helper\HtmlElement',
                ),
            ),
        );
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
        $serviceManager = $e->getApplication()->getServiceManager();

        // Register form element view helpers
        $formElementHelper = $serviceManager->get('ViewHelperManager')->get('formElement');
        $formElementHelper->addClass('Library\Form\Element\SelectSimple', 'FormSelectSimple');
        $formElementHelper->addType('select_untranslated', 'FormSelectUntranslated');

        \Zend\Filter\StaticFilter::setPluginManager($serviceManager->get('FilterManager'));
        \Zend\Validator\StaticValidator::setPluginManager($serviceManager->get('ValidatorManager'));
    }

    /**
     * Get path to module directory
     *
     * @param string $path Optional path component that is appended to the module root path
     * @return string Absolute path to requested file/directory (directories without trailing slash)
     */
    public static function getPath($path = '')
    {
        return \Library\Application::getPath('module/Library/' . $path);
    }
}
