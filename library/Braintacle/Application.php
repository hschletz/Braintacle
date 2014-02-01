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
 *
 * @package Library
 */
/**
 * Bootstrap class for all applications that use the Braintacle API.
 * @package Library
 */
class Braintacle_Application
{
    /**
     * Indicator for include path set
     * @var bool
     */
    static protected $_includePathSet;

    /**
     * Bootstrap the application
     *
     * Call this instead of Zend_Application::bootstrap().
     */
    static function init()
    {
        // Set up PHP environment.
        ini_set('session.auto_start', false); // conflicts with Zend_Session
        session_cache_limiter('nocache'); // Default headers to prevent caching
        self::setIncludePath();

        // Create application, bootstrap, and run
        require_once 'Zend/Application.php';
        $application = new Zend_Application(
            self::getEnvironment(),
            self::getApplicationPath() . '/configs/application.ini'
        );
        $application->setBootstrap(APPLICATION_PATH . '/Bootstrap.php');
        $application->bootstrap()
                    ->run();
    }

    /**
     * Set include path
     */
    static function setIncludePath()
    {
        if (!self::$_includePathSet) {
            set_include_path(
                implode(
                    PATH_SEPARATOR,
                    array(
                        realpath(self::getApplicationPath() . '/../library'),
                        realpath(self::getApplicationPath() . '/../library/PEAR'),
                        get_include_path(),
                    )
                )
            );
            self::$_includePathSet = true;
        }
    }

    /**
     * Determine and set application path
     * @return string Value the APPLICATION_PATH constant
     */
    static function getApplicationPath()
    {
        if (!defined('APPLICATION_PATH')) {
            define(
                'APPLICATION_PATH',
                realpath(dirname(__FILE__) . '/../../application')
            );
        }
        return APPLICATION_PATH;
    }

    /**
     * Determine application environment
     * @return string Either the APPLICATION_ENV environment variable or 'production' if this is undefined.
     */
    static function getEnvironment()
    {
        if (!defined('APPLICATION_ENV')) {
            $env = getenv('APPLICATION_ENV');
            if (!$env) {
                $env = 'production';
            }
            define('APPLICATION_ENV', $env);
        }
        return APPLICATION_ENV;
    }

    /**
     * Check for CLI SAPI
     * @return bool
     */
    static function isCli()
    {
        return PHP_SAPI == 'cli';
    }

}
