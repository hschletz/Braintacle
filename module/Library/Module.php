<?php
/**
 * The Library module
 *
 * Copyright (C) 2011-2021 Holger Schletz <holger.schletz@web.de>
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

use Laminas\ModuleManager\Feature;
use Laminas\ServiceManager\Factory\InvokableFactory;

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
    /** {@inheritdoc} */
    public function getConfig()
    {
        return array(
            'controller_plugins' => array(
                'aliases' => array(
                    '_' => 'Library\Mvc\Controller\Plugin\TranslationHelper',
                    'RedirectToRoute' => 'Library\Mvc\Controller\Plugin\RedirectToRoute',
                    'redirectToRoute' => 'Library\Mvc\Controller\Plugin\RedirectToRoute',
                    'UrlFromRoute' => 'Library\Mvc\Controller\Plugin\UrlFromRoute',
                    'urlFromRoute' => 'Library\Mvc\Controller\Plugin\UrlFromRoute',
                ),
                'factories' => array(
                    'Library\Mvc\Controller\Plugin\TranslationHelper' => 'Laminas\ServiceManager\Factory\InvokableFactory',
                    'Library\Mvc\Controller\Plugin\RedirectToRoute' => 'Laminas\ServiceManager\Factory\InvokableFactory',
                    'Library\Mvc\Controller\Plugin\UrlFromRoute' => 'Laminas\ServiceManager\Factory\InvokableFactory',
                )
            ),
            'filters' => array(
                'aliases' => array(
                    'Library\EmptyArray' => 'Library\Filter\EmptyArray',
                    'Library\FixEncodingErrors' => 'Library\Filter\FixEncodingErrors',
                    'Library\LogLevel' => 'Library\Filter\LogLevel',
                ),
                'factories' => array(
                    'Library\Filter\EmptyArray' => 'Laminas\ServiceManager\Factory\InvokableFactory',
                    'Library\Filter\FixEncodingErrors' => 'Laminas\ServiceManager\Factory\InvokableFactory',
                    'Library\Filter\LogLevel' => 'Laminas\ServiceManager\Factory\InvokableFactory',
                ),
            ),
            'log' => array(
                'Library\Logger' => array(
                    // Ready-to-use logger instance with a noop writer attached.
                    // Applications can add their own writer.
                    'writers' => array(
                        array(
                            'name' => 'noop',
                        ),
                    ),
                ),
            ),
            'service_manager' => array(
                'delegators' => array(
                    'Laminas\Mvc\I18n\Translator' => array('Library\I18n\Translator\DelegatorFactory'),
                ),
                'factories' => [
                    'Library\ArchiveManager' => 'Laminas\ServiceManager\Factory\InvokableFactory',
                    'Library\HttpClient' => function () {
                        return new \Laminas\Http\Client();
                    },
                    'Library\I18n\Translator\DelegatorFactory' => 'Laminas\ServiceManager\Factory\InvokableFactory',
                    'Library\Log\Writer\StdErr' => function () {
                        return new \Laminas\Log\Writer\Stream('php://stderr');
                    },
                    'Library\Now' => function () {
                        return new \DateTime;
                    },
                    'Library\Random' => 'Laminas\ServiceManager\Factory\InvokableFactory',
                    'Library\UserConfig' => 'Library\Service\UserConfigFactory',
                ],
                'shared' => [
                    'Library\HttpClient' => false,
                    'Library\Now' => false,
                ],
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
                'aliases' => array(
                    'Po' => 'Library\I18n\Translator\Loader\Po',
                ),
                'factories' => array(
                    'Library\I18n\Translator\Loader\Po' => 'Laminas\ServiceManager\Factory\InvokableFactory',
                ),
            ),
            'validators' => array(
                'aliases' => array(
                    'Library\DirectoryWritable' => 'Library\Validator\DirectoryWritable',
                    'Library\FileReadable' => 'Library\Validator\FileReadable',
                    'Library\IpNetworkAddress' => Validator\IpNetworkAddress::class,
                    'Library\LogLevel' => 'Library\Validator\LogLevel',
                    'Library\NotInArray' => 'Library\Validator\NotInArray',
                    'Library\ProductKey' => 'Library\Validator\ProductKey',
                ),
                'factories' => array(
                    'Library\Validator\DirectoryWritable' => 'Laminas\ServiceManager\Factory\InvokableFactory',
                    'Library\Validator\FileReadable' => 'Laminas\ServiceManager\Factory\InvokableFactory',
                    Validator\IpNetworkAddress::class => InvokableFactory::class,
                    'Library\Validator\LogLevel' => 'Laminas\ServiceManager\Factory\InvokableFactory',
                    'Library\Validator\NotInArray' => 'Laminas\ServiceManager\Factory\InvokableFactory',
                    'Library\Validator\ProductKey' => 'Laminas\ServiceManager\Factory\InvokableFactory',
                ),
            ),
            'view_helpers' => array(
                'aliases' => array(
                    'formSelectSimple' => 'Library\View\Helper\FormSelectSimple',
                    'formSelectUntranslated' => 'Library\View\Helper\FormSelectUntranslated',
                    'formYesNo' => 'Library\View\Helper\FormYesNo',
                    'htmlElement' => 'Library\View\Helper\HtmlElement',
                ),
                'factories' => array(
                    'Library\View\Helper\FormSelectSimple' => 'Laminas\ServiceManager\Factory\InvokableFactory',
                    'Library\View\Helper\FormSelectUntranslated' => 'Laminas\ServiceManager\Factory\InvokableFactory',
                    'Library\View\Helper\FormYesNo' => 'Library\View\Helper\Service\FormYesNoFactory',
                    'Library\View\Helper\HtmlElement' => 'Laminas\ServiceManager\Factory\InvokableFactory',
                ),
            ),
        );
    }

    /** {@inheritdoc} */
    public function getAutoloaderConfig()
    {
        return array(
            'Laminas\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__,
                ),
            ),
        );
    }

    /** {@inheritdoc} */
    public function onBootstrap(\Laminas\EventManager\EventInterface $e)
    {
        $serviceManager = $e->getApplication()->getServiceManager();

        // Register form element view helpers
        $formElementHelper = $serviceManager->get('ViewHelperManager')->get('formElement');
        $formElementHelper->addClass('Library\Form\Element\SelectSimple', 'formSelectSimple');
        $formElementHelper->addType('select_untranslated', 'formSelectUntranslated');

        \Laminas\Filter\StaticFilter::setPluginManager($serviceManager->get('FilterManager'));
        \Laminas\Validator\StaticValidator::setPluginManager($serviceManager->get('ValidatorManager'));
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
