<?php

/**
 * The Library module
 *
 * Copyright (C) 2011-2025 Holger Schletz <holger.schletz@web.de>
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

use Laminas\Di\ConfigInterface;
use Laminas\Di\Container\ConfigFactory;
use Laminas\Di\Container\InjectorFactory;
use Laminas\Di\InjectorInterface;
use Laminas\ModuleManager\Feature;
use Library\Mvc\Controller\Plugin\RedirectToRoute;
use Library\Mvc\Controller\Plugin\UrlFromRoute;
use Library\Mvc\Service\RedirectToRouteFactory;
use Library\Mvc\Service\UrlFromRouteFactory;

/**
 * The Library module
 *
 * This module provides a library of general purpose classes (not specific to
 * other modules). Provided view helpers etc. are automatically registered and
 * don't need to be loaded explicitly.
 *
 * @codeCoverageIgnore
 */
class Module implements Feature\ConfigProviderInterface
{
    /** {@inheritdoc} */
    public function getConfig()
    {
        return array(
            'controller_plugins' => array(
                'aliases' => array(
                    'RedirectToRoute' => 'Library\Mvc\Controller\Plugin\RedirectToRoute',
                    'redirectToRoute' => 'Library\Mvc\Controller\Plugin\RedirectToRoute',
                    'UrlFromRoute' => 'Library\Mvc\Controller\Plugin\UrlFromRoute',
                    'urlFromRoute' => 'Library\Mvc\Controller\Plugin\UrlFromRoute',
                ),
                'factories' => array(
                    RedirectToRoute::class => RedirectToRouteFactory::class,
                    UrlFromRoute::class => UrlFromRouteFactory::class,
                )
            ),
            'service_manager' => array(
                'factories' => [
                    ConfigInterface::class => ConfigFactory::class,
                    InjectorInterface::class => InjectorFactory::class,
                ],
            ),
            'validators' => array(
                'aliases' => array(
                    'Library\DirectoryWritable' => 'Library\Validator\DirectoryWritable',
                    'Library\FileReadable' => 'Library\Validator\FileReadable',
                    'Library\IpNetworkAddress' => Validator\IpNetworkAddress::class,
                    'Library\NotInArray' => 'Library\Validator\NotInArray',
                    'Library\ProductKey' => 'Library\Validator\ProductKey',
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

    /** @psalm-suppress PossiblyUnusedMethod */
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
