<?php
/**
 * The Console module
 *
 * Copyright (C) 2011-2021 Holger Schletz <holger.schletz@web.de>
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

use Laminas\ModuleManager\Feature;
use Laminas\Mvc\MvcEvent;

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
    /** {@inheritdoc} */
    public function init(\Laminas\ModuleManager\ModuleManagerInterface $manager)
    {
        $manager->loadModule('Library');
        $manager->loadModule('Model');
        $manager->loadModule('Protocol');
    }

    /** {@inheritdoc} */
    public function getConfig()
    {
        return require(__DIR__ . '/module.config.php');
    }

    /** {@inheritdoc} */
    public function getAutoloaderConfig()
    {
        return array(
            'Laminas\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__,
                ),
            ),
        );
    }

    /** {@inheritdoc} */
    public function onBootstrap(\Laminas\EventManager\EventInterface $e)
    {
        $eventManager = $e->getParam('application')->getEventManager();
        $eventManager->attach(MvcEvent::EVENT_ROUTE, array($this, 'setValidatorTranslator'));
        $eventManager->attach(MvcEvent::EVENT_ROUTE, array($this, 'forceLogin'));
        $eventManager->attach(MvcEvent::EVENT_RENDER, array($this, 'setStrictVars'));
        $eventManager->attach(MvcEvent::EVENT_RENDER, array($this, 'setMenu'));
        $eventManager->attach(MvcEvent::EVENT_RENDER, array($this, 'setLayoutTitle'));
        $eventManager->attach(MvcEvent::EVENT_DISPATCH_ERROR, array($this, 'onError'));
        $eventManager->attach(MvcEvent::EVENT_RENDER_ERROR, array($this, 'onError'));

        // Evaluate locale from HTTP header. Affects translations, date/time rendering etc.
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            \Locale::setDefault(\Locale::acceptFromHttp($_SERVER['HTTP_ACCEPT_LANGUAGE']));
        }
    }

    /**
     * Hook to set the default translator for validators
     *
     * Also disables translations in the FormElementErrors view helper.
     * Otherwise it would try to translate the already translated messages from
     * the validators. Translation must be left to the validators because only
     * validators can handle placeholders in message templates.
     *
     * This is invoked by the "route" event to avoid invocation of factories
     * within the bootstrap event which would cause problems for testing.
     *
     * @param \Laminas\Mvc\MvcEvent $e MVC event
     */
    public function setValidatorTranslator(\Laminas\Mvc\MvcEvent $e)
    {
        $serviceManager = $e->getApplication()->getServiceManager();
        \Laminas\Validator\AbstractValidator::setDefaultTranslator($serviceManager->get('MvcTranslator'));
        $serviceManager->get('ViewHelperManager')->get('FormElementErrors')->setTranslateMessages(false);
    }

    /**
     * Hook to redirect unauthenticated requests to the login page
     *
     * @param \Laminas\Mvc\MvcEvent $e MVC event
     * @return mixed Redirect response (\Laminas\Stdlib\ResponseInterface) or NULL to continue
     */
    public function forceLogin(\Laminas\Mvc\MvcEvent $e)
    {
        // If user is not yet authenticated, redirect to the login page except
        // for the login controller, in which case redirection would result in
        // an infinite loop.
        $serviceManager = $e->getApplication()->getServiceManager();
        if (!$serviceManager->get('Laminas\Authentication\AuthenticationService')->hasIdentity() and
            $e->getRouteMatch()->getParam('controller') != 'login'
        ) {
            // Preserve URI of current request for redirect after successful login
            $session = new \Laminas\Session\Container('login');
            $session->originalUri = $e->getRequest()->getUriString();

            $location = $e->getRouter()->assemble(
                array('controller' => 'login', 'action' => 'login'),
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
     * @param \Laminas\Mvc\MvcEvent $e MVC event
     */
    public function setStrictVars(\Laminas\EventManager\EventInterface $e)
    {
        $this->_setStrictVars($e->getViewModel());
    }

    /**
     * Set strict vars on a view model recursively
     *
     * @param \Laminas\View\Model\ViewModel $model
     */
    protected function _setStrictVars(\Laminas\View\Model\ViewModel $model)
    {
        $vars = $model->getVariables();
        if (!$vars instanceof \Laminas\View\Variables) {
            $vars = new \Laminas\View\Variables($vars);
        }
        $vars->setStrictVars(true);
        $model->setVariables($vars, true);
        foreach ($model->getChildren() as $child) {
            $this->_setStrictVars($child);
        }
    }

    /**
     * Hook to inject the main menu
     *
     * This is invoked by the "render" event.
     *
     * @param \Laminas\Mvc\MvcEvent $e MVC event
     */
    public function setMenu(\Laminas\EventManager\EventInterface $e)
    {
        $e->getViewModel()->menu = 'Console\Navigation\MainMenu';
    }

    /**
     * Hook to set the page title
     *
     * This is invoked by the "render" event.
     *
     * @param \Laminas\Mvc\MvcEvent $e MVC event
     */
    public function setLayoutTitle(\Laminas\Mvc\MvcEvent $e)
    {
        $headTitleHelper = $e->getApplication()->getServiceManager()->get('ViewHelperManager')->get('headTitle');
        $headTitleHelper->setTranslatorEnabled(false);
        $headTitleHelper->append('Braintacle'); // TODO: append page-specific information
    }

    /**
     * Hook to inject the controller name and request object into the error template
     *
     * This is triggered by EVENT_DISPATCH_ERROR and EVENT_RENDER_ERROR.
     *
     * @param \Laminas\Mvc\MvcEvent $e MVC event
     */
    public function onError(\Laminas\Mvc\MvcEvent $e)
    {
        $result = $e->getResult();
        $result->serviceManager = $e->getApplication()->getServiceManager();
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
    public static function getPath($path = '')
    {
        return \Library\Application::getPath('module/Console/' . $path);
    }
}
