<?php
/**
 * Bootstrap class for all applications that use the Braintacle API.
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

/**
 * Bootstrap class for all applications that use the Braintacle API.
 *
 * To bootstrap a Braintacle application, include this class manually and call
 * init().
 */
class Application
{
    /**
     * The ZF1 application object (must be run manually if required)
     * @var \Zend_Application
     */
    public static $application;

    /**
     * Base path of the ZF1 library
     * @var string
     */
    public static $zf1Path;

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
     * @param bool $run Run the application after initialization.
     * @codeCoverageIgnore
     */
    static function init($module, $run=null)
    {
        // Set up PHP environment.
        ini_set('session.auto_start', false); // conflicts with Zend_Session
        session_cache_limiter('nocache'); // Default headers to prevent caching
        ini_set('magic_quotes_runtime', false);

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

        // PEAR libraries are not suitable for autoloading. Add them to include path instead.
        set_include_path(get_include_path() . PATH_SEPARATOR . self::getPath('library/PEAR'));

        // Bootstrap ZF1 application part, but don't run it yet.
        // It is run at a later point if required.
        // TODO: remove APPLICATION_PATH and APPLICATION_ENV when no longer used
        define('APPLICATION_PATH', self::getPath('application'));
        $environment = self::getEnvironment();

        // Get absolute path to ZF1 library
        $file = new \SplFileInfo('Zend/Application.php');
        self::$zf1Path = dirname($file->openFile('r', true)->getRealPath());

        require_once 'Zend/Application.php';
        self::$application = new \Zend_Application(
            ($environment == 'test' ? 'development' : $environment),
            self::getPath('application/configs/application.ini')
        );
        self::$application->setBootstrap(self::getPath('application/Bootstrap.php'));
        self::$application->bootstrap();
        \Zend_Session::start(); // Required to avoid interference with \Zend\Session

        $application = \Zend\Mvc\Application::init(
            array(
                'modules' => array($module),
                'module_listener_options' => array(
                    'module_paths' => array(
                        'Cli' => self::getPath('module/Cli'),
                        'Console' => self::getPath('module/Console/Console'),
                        'Database' => self::getPath('module/Database'),
                        'Library' => self::getPath('module/Library'),
                        'Model' => self::getPath('module/Model'),
                    ),
                ),
            )
        );
        self::$_serviceManager = $application->getServiceManager();
        if ($run === null) {
            if ($module == 'Console') {
                $run = true;
            } else {
                $run = false;
            }
        }
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
    static function getPath($path='')
    {
        $realPath = realpath(__DIR__ . '/../../' . $path);
        if (!$realPath) {
            throw new \LogicException("Invalid application path: $path");
        }
        return $realPath;
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
    static function getEnvironment()
    {
        $environment = getenv('APPLICATION_ENV') ?: 'production';
        if ($environment != 'production' and $environment != 'development' and $environment != 'test') {
            throw new \DomainException('APPLICATION_ENV environment variable has invalid value: ' . $environment);
        }
        if (!defined('APPLICATION_ENV')) {
            // @codeCoverageIgnoreStart
            define('APPLICATION_ENV', ($environment == 'test' ? 'development' : $environment));
        }
        // @codeCoverageIgnoreEnd
        return $environment;
    }

    /**
     * Check for production environment
     *
     * @return bool
     */
    static function isProduction()
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
    static function isDevelopment()
    {
        $environment = self::getEnvironment();
        return ($environment == 'development' or $environment == 'test');
    }

    /**
     * Check for test environment used by unit tests
     *
     * @return bool
     */
    static function isTest()
    {
        return self::getEnvironment() == 'test';
    }

    /**
     * Check for CLI SAPI
     *
     * @return bool
     */
    static function isCli()
    {
        return PHP_SAPI == 'cli';
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
