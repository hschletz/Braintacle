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
    protected function _initNavigation()
    {
        if (Application::isCli()) {
            return;
        }

        $this->bootstrap('autoload');
        $navigation = new Zend_Navigation;

        // "Inventory" menu
        $page = new Zend_Navigation_Page_Mvc;
        $page->setLabel('Inventory')
             ->setController('computer')
             ->setAction('index');
        $navigation->addPage($page);

        $sub = new Zend_Navigation_Page_Mvc;
        $sub->setLabel('Computers')
            ->setController('computer')
            ->setAction('index');
        $page->addPage($sub);

        $sub = new Zend_Navigation_Page_Mvc;
        $sub->setLabel('Software')
            ->setController('software')
            ->setAction('index');
        $page->addPage($sub);

        $sub = new Zend_Navigation_Page_Mvc;
        $sub->setLabel('Network')
            ->setController('network')
            ->setAction('index');
        $page->addPage($sub);

        $sub = new Zend_Navigation_Page_Mvc;
        $sub->setLabel('Duplicates')
            ->setController('duplicates')
            ->setAction('index');
        $page->addPage($sub);

        $sub = new Zend_Navigation_Page_Mvc;
        $sub->setLabel('Import')
            ->setController('computer')
            ->setAction('import');
        $page->addPage($sub);

        // "Groups" menu
        $page = new Zend_Navigation_Page_Mvc;
        $page->setLabel('Groups')
             ->setController('group')
             ->setAction('index');
        $navigation->addPage($page);

        // "Packages" menu
        $page = new Zend_Navigation_Page_Mvc;
        $page->setLabel('Packages')
             ->setController('package')
             ->setAction('index');
        $navigation->addPage($page);

        $sub = new Zend_Navigation_Page_Mvc;
        $sub->setLabel('Overview')
            ->setController('package')
            ->setAction('index');
        $page->addPage($sub);

        $sub = new Zend_Navigation_Page_Mvc;
        $sub->setLabel('Build')
            ->setController('package')
            ->setAction('build');
        $page->addPage($sub);

        // "Licenses" menu
        $page = new Zend_Navigation_Page_Mvc;
        $page->setLabel('Licenses')
             ->setController('licenses')
             ->setAction('index');
        $navigation->addPage($page);

        // Search button
        $page = new Zend_Navigation_Page_Mvc;
        $page->setLabel('Search')
             ->setController('computer')
             ->setAction('search');
        $navigation->addPage($page);

        // "Preferences" menu
        $page = new Zend_Navigation_Page_Mvc;
        $page->setLabel('Preferences')
             ->setController('preferences')
             ->setAction('index');
        $navigation->addPage($page);

        $sub = new Zend_Navigation_Page_Mvc;
        $sub->setLabel('Display')
            ->setController('preferences')
            ->setAction('display');
        $page->addPage($sub);

        $sub = new Zend_Navigation_Page_Mvc;
        $sub->setLabel('Inventory')
            ->setController('preferences')
            ->setAction('inventory');
        $page->addPage($sub);

        $sub = new Zend_Navigation_Page_Mvc;
        $sub->setLabel('Agent')
            ->setController('preferences')
            ->setAction('agent');
        $page->addPage($sub);

        $sub = new Zend_Navigation_Page_Mvc;
        $sub->setLabel('Packages')
            ->setController('preferences')
            ->setAction('packages');
        $page->addPage($sub);

        $sub = new Zend_Navigation_Page_Mvc;
        $sub->setLabel('Download')
            ->setController('preferences')
            ->setAction('download');
        $page->addPage($sub);

        $sub = new Zend_Navigation_Page_Mvc;
        $sub->setLabel('Groups')
            ->setController('preferences')
            ->setAction('groups');
        $page->addPage($sub);

        $sub = new Zend_Navigation_Page_Mvc;
        $sub->setLabel('Network scanning')
            ->setController('preferences')
            ->setAction('networkscanning');
        $page->addPage($sub);

        $sub = new Zend_Navigation_Page_Mvc;
        $sub->setLabel('Raw data')
            ->setController('preferences')
            ->setAction('rawdata');
        $page->addPage($sub);

        $sub = new Zend_Navigation_Page_Mvc;
        $sub->setLabel('Filters')
            ->setController('preferences')
            ->setAction('filters');
        $page->addPage($sub);

        $sub = new Zend_Navigation_Page_Mvc;
        $sub->setLabel('System')
            ->setController('preferences')
            ->setAction('system');
        $page->addPage($sub);

        $sub = new Zend_Navigation_Page_Mvc;
        $sub->setLabel('Users')
            ->setController('accounts')
            ->setAction('index');
        $page->addPage($sub);

        Zend_Registry::set('Zend_Navigation', $navigation);
    }

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

    protected function _initActionHelpers()
    {
        Zend_Controller_Action_HelperBroker::addPath(
            APPLICATION_PATH . '/controllers/helpers'
        );
    }

    protected function _initViewHelpers()
    {
        if (Application::isCli() and !Application::isTest()) {
            return;
        }

        $layout = Zend_Layout::startMvc();
        $layout->setLayoutPath(APPLICATION_PATH . '/layouts');
        $view = $layout->getView();
        $view->strictVars(true);

        $pluginLoader = new Zend_Loader_PluginLoader;
        $pluginLoader->addPrefixPath(
            'Zend_View_Helper',
            Application::$zf1Path . '/View/Helper'
        );
        $pluginLoader->addPrefixPath(
            'Zend_View_Helper_Navigation',
            Application::$zf1Path . '/View/Helper/Navigation'
        );
        $view->setPluginLoader($pluginLoader, 'helper');

        $view->doctype('HTML4_STRICT');

        $view->headMeta()->appendHttpEquiv(
            'Content-Type', 'text/html; charset=UTF-8'
        );

        $view->headTitle()->setSeparator(' - ');
        $view->headTitle('Braintacle');
    }

    protected function _initController()
    {
        $controller = Zend_Controller_Front::getInstance();

        // Skip ZF1 handling of exceptions; have them handled by the ZF2 error handler instead
        $controller->throwExceptions(true);

        $controller->setControllerDirectory(APPLICATION_PATH . '/controllers');

        $route = new Braintacle_Controller_Router_Route_Module(
            array(),
            $controller->getDispatcher(),
            $controller->getRequest()
        );
        $controller->getRouter()->addRoute('default', $route);

        if (!Application::isCli()) {
            $controller->registerPlugin(new Braintacle_Controller_Plugin_ForceLogin);
        }
    }

    protected function _initAutoload()
    {
        // Autoloader for old library code
        \Zend\Loader\AutoloaderFactory::factory(
            array(
                '\Zend\Loader\StandardAutoloader' => array(
                    'prefixes' => array(
                        'Zend' => Application::$zf1Path,
                        'Braintacle' => Application::getPath('library/Braintacle'),
                    )
                ),
            )
        );

        // Autoloader for Zend_Filter_Inflector
        $pluginLoader = new Zend_Loader_PluginLoader(
            array(
                'Zend_Filter' => Application::$zf1Path . '/Filter',
            ),
            'Zend_Filter_Inflector'
        );

        // ZF1 module autoloader
        $moduleLoader = new Zend_Application_Module_Autoloader(
            array(
                'namespace' => '',
                'basePath' => __DIR__
            )
        );
        return $moduleLoader;
    }

}
