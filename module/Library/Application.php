<?php
/**
 * Bootstrap class for all applications that use the Braintacle API.
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

/**
 * Bootstrap class for all applications that use the Braintacle API.
 *
 * To bootstrap a Braintacle application, include this class manually and call
 * init().
 */
class Application
{
    /**
     * Cached content of config file, managed and accessed via getConfig()
     * @var array
     */
    protected static $_config;

    /**
     * Set up application environment
     *
     * This sets up the PHP environment and autoloaders, reads the config file,
     * loads the provided module and returns the MVC application.
     *
     * @param string|array $config Path to config file or array with compatible structure
     * @param string $module Module to load
     * @return \Zend\Mvc\Application
     * @codeCoverageIgnore
     */
    public static function init($config, $module)
    {
        // Set up PHP environment.
        session_cache_limiter('nocache'); // Default headers to prevent caching

        // Evaluate locale from HTTP header. Affects translations, date/time rendering etc.
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            \Locale::setDefault(\Locale::acceptFromHttp($_SERVER['HTTP_ACCEPT_LANGUAGE']));
        }

        if (is_string($config)) {
            $reader = new \Zend\Config\Reader\Ini;
            static::$_config = $reader->fromFile($config);
        } else {
            static::$_config = $config;
        }

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
            'modules' => array($module),
            'module_listener_options' => array(
                'module_paths' => array(static::getPath('module')),
            ),
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

    /**
     * Get application configuration set via init()
     *
     * @return mixed
     * @codeCoverageIgnore
     */
    public static function getConfig()
    {
        return static::$_config;
    }

    /**
     * Determine application environment
     *
     * @return string Either the APPLICATION_ENV environment variable or 'production' if this is undefined.
     * @throws \DomainException if the value is invalid
     */
    public static function getEnvironment()
    {
        $environment = getenv('APPLICATION_ENV') ?: 'production';
        if ($environment != 'production' and $environment != 'development') {
            throw new \DomainException('APPLICATION_ENV environment variable has invalid value: ' . $environment);
        }
        return $environment;
    }

    /**
     * Check for development environment
     *
     * This returns true if the APPLICATION_ENV environment variable is either
     * "development" or "test". Check isTest() additionally if you need to need
     * to check for "development" explicitly.
     * @return bool
     */
    public static function isDevelopment()
    {
        $environment = self::getEnvironment();
        return ($environment == 'development' or $environment == 'test');
    }
}
