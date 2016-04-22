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
     * The application's service manager instance
     * @var \Zend\ServiceManager\ServiceManager
     */
    protected static $_serviceManager;

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

        // Set up autoloader
        $vendorDir = __DIR__ . '/../../vendor';
        if (is_dir($vendorDir)) {
            // generated by composer
            require_once "$vendorDir/autoload.php";
        } else {
            // manual setup
            require_once 'Nada.php';
            require_once 'Zend/Loader/AutoloaderFactory.php';
            $autoloader = array('autoregister_zf' => true);
            $vfsStreamPath = stream_resolve_include_path('org/bovigo/vfs');
            if ($vfsStreamPath) {
                $autoloader['namespaces'] = array('org\bovigo\vfs' => $vfsStreamPath);
            }
            \Zend\Loader\AutoloaderFactory::factory(array('\Zend\Loader\StandardAutoloader' => $autoloader));
        }

        if (is_string($config)) {
            $reader = new \Zend\Config\Reader\Ini;
            static::$_config = $reader->fromFile($config);
        } else {
            static::$_config = $config;
        }

        $application = \Zend\Mvc\Application::init(
            array(
                'modules' => array($module),
                'module_listener_options' => array(
                    'module_paths' => array(
                        'Console' => self::getPath('module/Console/Console'),
                        self::getPath('module'),
                    ),
                ),
            )
        );
        self::$_serviceManager = $application->getServiceManager();

        return $application;
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
     * This should rarely be used directly. Use isProduction(), isDevelopment()
     * or isTest() instead.
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
     * Check for production environment
     *
     * @return bool
     */
    public static function isProduction()
    {
        return self::getEnvironment() == 'production';
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

    /**
     * Get a service from the application's service manager.
     *
     * Objects created through the service manager (view helpers etc.) should
     * preferrably implement \Zend\ServiceManager\ServicLocatorAwareInterface
     * instead of calling this method, making the code more portable by reducing
     * external depenencies. Other objects should have the service manager
     * instance injected manually. This method should only be used where this
     * functionality is not available. One use case is unit testing where the
     * initialization code bypasses this part of the Zend Framework.
     *
     * @param string $name Service name
     * @return mixed Registered service
     */
    public static function getService($name)
    {
        return self::$_serviceManager->get($name);
    }
}
