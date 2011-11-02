<?php
/**
 * Application bootstrap file.
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
 */

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
    protected function _initNavigation()
    {
        if (Braintacle_Application::isCli()) {
            return;
        }

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

        // Search button
        $page = new Zend_Navigation_Page_Mvc;
        $page->setLabel('Search')
             ->setController('computer')
             ->setAction('search');
        $navigation->addPage($page);

        Zend_Registry::set('Zend_Navigation', $navigation);
    }

    protected function _initDatabase()
    {
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
                'gettext',
                realpath(dirname(APPLICATION_PATH) . DIRECTORY_SEPARATOR . 'languages'),
                $language,
                array(
                    'scan' => Zend_Translate::LOCALE_DIRECTORY,
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
        if (Braintacle_Application::isCli()) {
            return;
        }

        $layout = Zend_Layout::startMvc();
        $layout->setLayoutPath(APPLICATION_PATH . '/layouts');
        $view = $layout->getView();
        $view->strictVars(true);

        $view->doctype('HTML4_STRICT');

        $view->headMeta()->appendHttpEquiv(
            'Content-Type', 'text/html; charset=UTF-8'
        );

        $view->headTitle()->setSeparator(' - ');
        $view->headTitle('Braintacle');
    }

    protected function _initController()
    {
        Zend_Controller_Front::getInstance()->setControllerDirectory(APPLICATION_PATH . '/controllers');
    }

    protected function _initHeaders()
    {
        if (Braintacle_Application::isCli()) {
            return;
        }
        // Ensure correct session settings before Zend_Auth invokes session
        $this->bootstrap('session');

        // Create objects (they are not yet initialized at this time)
        $request = new Zend_Controller_Request_Http;
        $response = new Zend_Controller_Response_Http;

        // Auto-determine base URL
        $request->setBaseUrl();
        // Don't cache any content.
        $response->setHeader('Cache-Control', 'no-store', true);

        // If user is not yet authenticated, redirect to the login page
        // except when URI contains /login, in which case redirection would
        // result in an endless loop. LoginController will handle the rest.
        if (!Zend_Auth::getInstance()->hasIdentity()
            and !preg_match('#/login(/|$)#', $request->getRequestUri())
        ) {
            $response->setRedirect($request->getBaseUrl() . '/login');
        }

        // Initialize controller
        $this->bootstrap('controller');
        $controller = Zend_Controller_Front::getInstance();
        $controller->setRequest($request);
        $controller->setResponse($response);
    }

    protected function _initAutoload()
    {
        $moduleLoader = new Zend_Application_Module_Autoloader(
            array(
                'namespace' => '',
                'basePath' => APPLICATION_PATH
            )
        );
        return $moduleLoader;
    }

}
