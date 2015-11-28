<?php
/**
 * Bootstrap class for all applications that use the Braintacle API.
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
     * Set up application environment, optionally run the application
     *
     * This sets up the PHP environment and autoloaders, loads the provided
     * module and optionally runs the MVC application. The default behavior for
     * the $run option depends on the module. This should rarely need to be
     * overridden except for testing.
     *
     * @param string $module Module to load
     * @param bool $run Run the application after initialization. Default: TRUE
     * @codeCoverageIgnore
     */
    public static function init($module, $run = true)
    {
        // Set up PHP environment.
        session_cache_limiter('nocache'); // Default headers to prevent caching

        // Evaluate locale from HTTP header. Affects translations, date/time rendering etc.
        \Locale::setDefault(\Locale::acceptFromHttp(@$_SERVER['HTTP_ACCEPT_LANGUAGE']));

        // Set up autoloader for ZF classes
        require_once('Zend/Loader/AutoloaderFactory.php');
        \Zend\Loader\AutoloaderFactory::factory(
            array(
                '\Zend\Loader\StandardAutoloader' => array(
                    'autoregister_zf' => true,
                )
            )
        );

        $application = \Zend\Mvc\Application::init(
            array(
                'modules' => array($module),
                'module_listener_options' => array(
                    'module_paths' => array(
                        'Console' => self::getPath('module/Console/Console'),
                        'Database' => self::getPath('module/Database'),
                        'Export' => self::getPath('module/Export'),
                        'Library' => self::getPath('module/Library'),
                        'Model' => self::getPath('module/Model'),
                        'PackageBuilder' => self::getPath('module/PackageBuilder'),
                        'Protocol' => self::getPath('module/Protocol'),
                        'DatabaseManager' => self::getPath('module/DatabaseManager'),
                    ),
                ),
            )
        );
        self::$_serviceManager = $application->getServiceManager();

        if ($run) {
            $application->run();
        }
    }

    /**
     * Get application directory
     *
     * @param string $path Optional path component that is appended to the application root path
     * @return string Absolute path to requested file/directory (directories without trailing slash)
     * @throws \LogicException if the requested path component does not exist
     * @codeCoverageIgnore
     */
    public static function getPath($path='')
    {
        $realPath = realpath(__DIR__ . '/../../' . $path);
        if (!$realPath) {
            throw new \LogicException("Invalid application path: $path");
        }
        return $realPath;
    }

    /**
     * Get application configuration from config file
     *
     * Loads and caches the config file. By default, the config file is expected
     * at config/braintacle.ini, but a different file name and path can be set
     * via the BRAINTACLE_CONFIG environment variable.
     *
     * @throws \RuntimeException if file cannot be parsed
     * @codeCoverageIgnore
     */
    public static function getConfig()
    {
        if (!static::$_config) {
            $filename = getenv('BRAINTACLE_CONFIG') ?: static::getPath('config/braintacle.ini');
            static::$_config = parse_ini_file($filename, true);
            if (!static::$_config) {
                throw new \RuntimeException('Could not read config file ' . $filename);
            }
        }
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
        if ($environment != 'production' and $environment != 'development' and $environment != 'test') {
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
     * Check for test environment used by unit tests
     *
     * @return bool
     */
    public static function isTest()
    {
        return self::getEnvironment() == 'test';
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

    /**
     * Get config fragment to load appropriate translation file
     *
     * If the default locale is for example de_DE, the file $basePath/de_DE.po
     * is tried first. If it does not exist, it is shortened to the base
     * language (i.e. $basePath/de.po). If that file does not exist either, an
     * empty array is returned. Otherwise the returned array can be merged with
     * the module's config to load the translations from the file.
     *
     * @param string $basePath
     * @return array
     */
    public static function getTranslationConfig($basePath)
    {
        $config = array();
        $locale = \Locale::getDefault();
        $translationFile = "$basePath/$locale.po";
        if (!is_file($translationFile)) {
            $locale = \Locale::getPrimaryLanguage($locale);
            $translationFile = "$basePath/$locale.po";
            if (!is_file($translationFile)) {
                $translationFile = null;
            }
        }
        if ($translationFile) {
            $config['translator'] = array(
                'translation_files' => array(
                    array(
                        'type' => 'Po',
                        'filename' => $translationFile,
                    ),
                ),
            );
        }
        return $config;
    }
}
