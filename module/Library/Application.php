<?php
/**
 * Bootstrap class for all applications that use the Braintacle API.
 *
 * Copyright (C) 2011-2013 Holger Schletz <holger.schletz@web.de>
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
     * Set up application environment and run the application
     *
     * This sets up the PHP environment and autoloaders, loads the provided
     * module and runs the MVC application. The "Cli" module has no MVC
     * functionality, so that only the initialization is performed in that case.
     *
     * @param string $module Module to load
     */
    static function init($module)
    {
        // Set up PHP environment.
        ini_set('session.auto_start', false); // conflicts with Zend_Session
        session_cache_limiter('nocache'); // Default headers to prevent caching
        ini_set('magic_quotes_runtime', false);

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
        set_include_path(get_include_path() . PATH_SEPARATOR . self::getApplicationPath('library/PEAR'));

        // Bootstrap ZF1 application part, but don't run it yet.
        // It is run at a later point if required.
        // TODO: remove APPLICATION_PATH and APPLICATION_ENV when no longer used
        define('APPLICATION_PATH', self::getApplicationPath('application'));
        self::getEnvironment();

        // Get absolute path to ZF1 library
        $file = new \SplFileInfo('Zend/Application.php');
        self::$zf1Path = dirname($file->openFile('r', true)->getRealPath());

        require_once 'Zend/Application.php';
        self::$application = new \Zend_Application(
            self::getEnvironment(),
            self::getApplicationPath('application/configs/application.ini')
        );
        self::$application->setBootstrap(self::getApplicationPath('application/Bootstrap.php'));
        self::$application->bootstrap();

        $application = \Zend\Mvc\Application::init(
            array(
                'modules' => array($module),
                'module_listener_options' => array(
                    'module_paths' => array(
                        'Cli' => self::getApplicationPath('module/Cli'),
                        'Console' => self::getApplicationPath('module/Console/Console'),
                        'Library' => self::getApplicationPath('module/Library'),
                    ),
                ),
            )
        );
        if ($module != 'Cli') {
            $application->run();
        }
    }

    /**
     * Get application directory
     *
     * @param string $path Optional path component that is appended to the application root path
     * @return string Absolute path to requested file/directory (directories without trailing slash)
     * @throws \LogicException if the requested path component does not exist
     */
    static function getApplicationPath($path='')
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
     * @return string Either the APPLICATION_ENV environment variable or 'production' if this is undefined.
     */
    static function getEnvironment()
    {
        $environment = getenv('APPLICATION_ENV') ?: 'production';
        if (!defined('APPLICATION_ENV')) {
            define('APPLICATION_ENV', $environment);
        }
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
     * @return bool
     */
    static function isDevelopment()
    {
        return self::getEnvironment() == 'development';
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

}
