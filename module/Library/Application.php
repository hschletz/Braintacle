<?php
/**
 * Bootstrap class for all applications that use the Braintacle API.
 *
 * Copyright (C) 2011-2018 Holger Schletz <holger.schletz@web.de>
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

/**
 * Bootstrap class for all applications that use the Braintacle API.
 */
class Application
{
    /**
     * Initialize MVC application
     *
     * @param string $module Module that provides the application
     * @return \Zend\Mvc\Application
     * @codeCoverageIgnore
     */
    public static function init($module)
    {
        return \Zend\Mvc\Application::init(static::getApplicationConfig($module));
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
                'Zend\Filter',
                'Zend\Form',
                'Zend\I18n',
                'Zend\Log',
                'Zend\Mvc\I18n',
                'Zend\Mvc\Plugin\FlashMessenger',
                'Zend\Navigation',
                'Zend\Router',
                'Zend\Validator',
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
