<?php

/**
 * Bootstrap class for all applications that use the Braintacle API.
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

namespace Library;

use Laminas\Di\Container\ServiceManager\AutowireFactory;
use Laminas\Filter\FilterPluginManager;
use Laminas\I18n\Translator\LoaderPluginManager;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Validator\ValidatorPluginManager;

/**
 * Bootstrap class for all applications that use the Braintacle API.
 */
class Application
{
    /**
     * Initialize MVC application
     *
     * @param string $module Module that provides the application
     * @return \Laminas\Mvc\Application
     * @codeCoverageIgnore
     */
    public static function init($module)
    {
        $application = \Laminas\Mvc\Application::init(static::getApplicationConfig($module));
        static::addAbstractFactories($application->getServiceManager());
        return $application;
    }

    /**
     * Add abstract DI factory to given service manager.
     *
     * Abstract factories are invoked in the same order in which they get added.
     * The abstract DI factory should act as a fallback only. It cannot be added
     * via config because other modules might add another abstract factory after
     * the DI factory.
     *
     * This method must be called after the service manager has been completely
     * configured.
     */
    public static function addAbstractFactories(ServiceManager $serviceManager)
    {
        $serviceManager->addAbstractFactory(AutowireFactory::class);
        $serviceManager->get(FilterPluginManager::class)->addAbstractFactory(AutowireFactory::class);
        $serviceManager->get(LoaderPluginManager::class)->addAbstractFactory(AutowireFactory::class);
        $serviceManager->get(ValidatorPluginManager::class)->addAbstractFactory(AutowireFactory::class);
    }

    /**
     * Get module config for application initialization
     *
     * @param string $module Module to load
     * @return array
     */
    public static function getApplicationConfig($module)
    {
        return array(
            'modules' => array(
                'Laminas\Filter',
                'Laminas\Form',
                'Laminas\I18n',
                'Laminas\Log',
                'Laminas\Mvc\I18n',
                'Laminas\Mvc\Plugin\FlashMessenger',
                'Laminas\Navigation',
                'Laminas\Router',
                'Laminas\Validator',
                $module,
            ),
            'module_listener_options' => array(
                'module_paths' => array(static::getPath('module'))
            )
        );
    }

    /**
     * Get application directory
     *
     * @param string $path Optional path component that is appended to the application root path
     * @return string Absolute path to requested file/directory (directories without trailing slash)
     * @throws \LogicException if the requested path component does not exist
     * @codeCoverageIgnore
     */
    public static function getPath($path = '')
    {
        $realPath = realpath(__DIR__ . '/../../' . $path);
        if (!$realPath) {
            throw new \LogicException("Invalid application path: $path");
        }
        return $realPath;
    }
}
