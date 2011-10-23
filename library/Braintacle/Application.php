<?php
/**
 * Bootstrap class for all applications that use the Braintacle API.
 *
 * $Id$
 *
 * Copyright (C) 2011 Holger Schletz <holger.schletz@web.de>
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
 * @filesource
 */
/**
 * Bootstrap class for all applications that use the Braintacle API.
 * @package Library
 */
class Braintacle_Application
{
    /**
     * Bootstrap the application
     *
     * Call this instead of Zend_Application::bootstrap().
     */
    static function init()
    {
        // Define path to application directory
        if (!defined('APPLICATION_PATH')) {
            define(
                'APPLICATION_PATH',
                realpath(dirname(__FILE__) . '/../../application')
            );
        }

        // Define application environment
        self::getEnvironment();

        // Set up PHP environment.
        ini_set('session.auto_start', false); // conflicts with Zend_Session

        // Ensure library/ is on include_path
        set_include_path(
            implode(
                PATH_SEPARATOR,
                array(
                    realpath(APPLICATION_PATH . '/../library'),
                    realpath(APPLICATION_PATH . '/../library/PEAR'),
                    get_include_path(),
                )
            )
        );

        // Create application, bootstrap, and run
        require_once 'Zend/Application.php';
        $application = new Zend_Application(
            APPLICATION_ENV,
            APPLICATION_PATH . '/configs/application.ini'
        );
        $application->setBootstrap(APPLICATION_PATH . '/Bootstrap.php');
        $application->bootstrap()
                    ->run();
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

}
