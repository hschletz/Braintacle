<?php
/**
 * Application bootstrap file.
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

use \Library\Application;

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
    protected function _initDatabase()
    {
        $this->bootstrap('autoload');

        try {
            $config = new Zend_Config_Ini(
                realpath(APPLICATION_PATH . '/../config/database.ini'),
                APPLICATION_ENV,
                true
            );
        } catch (Zend_Exception $exception) {
            print 'Please create a file "database.ini" in the config/ directory.<br />';
            print 'A sample file can be found in the doc/ directory.';
            exit;
        }

        // Force UTF-8 client encoding regardless of configuration
        $config->params->charset = 'utf8';
        // Force lower case identifiers.
        $config->params->options = array (
            Zend_Db::AUTO_QUOTE_IDENTIFIERS => false,
            Zend_Db::CASE_FOLDING => Zend_Db::CASE_LOWER
        );
        $db = Zend_Db::factory($config);
        $db->setFetchMode(Zend_Db::FETCH_OBJ);
        Zend_Registry::set('db', $db);
        Zend_Db_Table::setDefaultAdapter($db);

        // Force strict behavior in development mode
        if (Application::isDevelopment() and !Application::isTest()) {
            Model_Database::getNada()->setStrictMode();
        }
    }

    protected function _initTranslate()
    {
        $locale = new Zend_Locale();
        Zend_Registry::set('Zend_Locale', $locale);

        $language = $locale->getLanguage();
        if ($language == 'en') {
            $translate = new Zend_Translate(
                'Braintacle_Translate_Adapter_Null'
            );
        } else {
            $translate = new Zend_Translate(
                'Braintacle_Translate_Adapter_Po',
                realpath(dirname(APPLICATION_PATH) . DIRECTORY_SEPARATOR . 'languages'),
                $language,
                array(
                    'scan' => Zend_Translate::LOCALE_DIRECTORY,
                    'ignore' => array('regex' => '/pot$/'),
                    'logUntranslated' => (APPLICATION_ENV != 'production'),
                    'disableNotices' => (APPLICATION_ENV == 'production')
                )
            );
        }
        Zend_Registry::set('Zend_Translate', $translate);
    }

    protected function _initAutoload()
    {
        // Autoloader for old library code
        \Zend\Loader\AutoloaderFactory::factory(
            array(
                '\Zend\Loader\StandardAutoloader' => array(
                    'prefixes' => array(
                        'Zend' => Application::$zf1Path,
                        'Model' => Application::getPath('application/models'),
                        'Braintacle' => Application::getPath('library/Braintacle'),
                    )
                ),
            )
        );
    }
}
