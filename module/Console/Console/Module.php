<?php
/**
 * The Console module
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

namespace Console;

use Zend\ModuleManager\Feature;
use Zend\Mvc\MvcEvent;

/**
 * This is the module for the web administration console.
 * @codeCoverageIgnore
 */
class Module implements
Feature\InitProviderInterface,
Feature\ConfigProviderInterface,
Feature\AutoloaderProviderInterface,
Feature\BootstrapListenerInterface
{
    /**
     * @internal
     */
    public function init(\Zend\ModuleManager\ModuleManagerInterface $manager)
    {
        $manager->loadModule('Library');
        $manager->loadModule('Model');
    }

    /**
     * @internal
     */
    public function getConfig()
    {
        return require(__DIR__ . '/../module.config.php');
    }

    /**
     * @internal
     */
    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__,
                ),
            ),
        );
    }

    /**
     * @internal
     */
    public function onBootstrap(\Zend\EventManager\EventInterface $e)
    {
        $eventManager = $e->getParam('application')->getEventManager();
        $eventManager->attach(MvcEvent::EVENT_ROUTE, array($this, 'forceLogin'));
        $eventManager->attach(MvcEvent::EVENT_RENDER, array($this, 'setStrictVars'));
        $eventManager->attach(MvcEvent::EVENT_RENDER, array($this, 'setLayoutTitle'));
        $eventManager->attach(MvcEvent::EVENT_DISPATCH_ERROR, array($this, 'onError'));
        $eventManager->attach(MvcEvent::EVENT_RENDER_ERROR, array($this, 'onError'));
    }

    /**
     * Hook to redirect unauthenticated requests to the login page
     *
     * @param \Zend\Mvc\MvcEvent $e MVC event
     * @return mixed Redirect response (\Zend\Stdlib\ResponseInterface) or NULL to continue
     */
    public function forceLogin(\Zend\Mvc\MvcEvent $e)
    {
        // If user is not yet authenticated, redirect to the login page except
        // for the login controller, in which case redirection would result in
        // an infinite loop.
        if (!$e->getApplication()->getServiceManager()->get('Library\AuthenticationService')->hasIdentity() and
            $e->getRouteMatch()->getParam('controller') != 'login' and
            !\Library\Application::isTest() // TODO: Provide test case
        ) {
            // Preserve URI of current request for redirect after successful login
            $session = new \Zend\Session\Container('login');
            $session->originalUri = $e->getRequest()->getUriString();

            $location = $e->getRouter()->assemble(
                array('controller' => 'login'),
                array('name' => 'console')
            );
            $response = $e->getResponse();
            $response->setStatusCode(302);
            $response->getHeaders()->addHeaderLine('Location', $location);
            return $response;
        }
    }

    /**
     * Hook to trigger notices on undefined view variables
     *
     * This is invoked by the "render" event.
     *
     * @param \Zend\Mvc\MvcEvent $e MVC event
     */
    public function setStrictVars(\Zend\EventManager\EventInterface $e)
    {
        $this->_setStrictVars($e->getViewModel());
    }

    /**
     * Set strict vars on a view model recursively
     *
     * @param \Zend\View\Model\ViewModel $model
     */
    protected function _setStrictVars(\Zend\View\Model\ViewModel $model)
    {
        $vars = $model->getVariables();
        if (!$vars instanceof \Zend\View\Variables) {
            $vars = new \Zend\View\Variables($vars);
        }
        $vars->setStrictVars(true);
        $model->setVariables($vars, true);
        foreach ($model->getChildren() as $child) {
            $this->_setStrictVars($child);
        }
    }

    /**
     * Hook to set the page title
     *
     * This is invoked by the "render" event.
     *
     * @param \Zend\Mvc\MvcEvent $e MVC event
     */
    public function setLayoutTitle(\Zend\Mvc\MvcEvent $e)
    {
        $headTitleHelper = $e->getApplication()->getServiceManager()->get('viewHelperManager')->get('headTitle');
        $headTitleHelper->setTranslatorEnabled(false);
        $headTitleHelper->append('Braintacle'); // TODO: append page-specific information
    }

    /**
     * Hook to inject the controller name and request object into the error template
     *
     * This is triggered by EVENT_DISPATCH_ERROR and EVENT_RENDER_ERROR.
     *
     * @param \Zend\Mvc\MvcEvent $e MVC event
     */
    public function onError(\Zend\Mvc\MvcEvent $e)
    {
        $result = $e->getResult();
        $result->request = $e->getRequest();
        $routeMatch = $e->getRouteMatch();
        if ($routeMatch) {
            $result->controller = $routeMatch->getParam('controller');
        }
    }

    /**
     * Get path to module directory
     *
     * @param string $path Optional path component that is appended to the module root path
     * @return string Absolute path to requested file/directory (directories without trailing slash)
     */
    static function getPath($path='')
    {
        return \Library\Application::getPath('module/Console/' . $path);
    }
}
