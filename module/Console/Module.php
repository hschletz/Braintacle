<?php

/**
 * The Console module
 *
 * Copyright (C) 2011-2024 Holger Schletz <holger.schletz@web.de>
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

use Console\Template\TemplateStrategy;
use Laminas\Form\View\Helper\FormElementErrors;
use Laminas\ModuleManager\Feature;
use Laminas\Mvc\I18n\Translator;
use Laminas\Mvc\MvcEvent;
use Laminas\Mvc\Plugin\FlashMessenger\View\Helper\FlashMessenger;
use Laminas\Validator\AbstractValidator;
use Laminas\View\View;

/**
 * This is the module for the web administration console.
 * @codeCoverageIgnore
 */
class Module implements
    Feature\InitProviderInterface,
    Feature\ConfigProviderInterface,
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
    public function onBootstrap(\Laminas\EventManager\EventInterface $e)
    {
        $eventManager = $e->getParam('application')->getEventManager();
        $eventManager->attach(MvcEvent::EVENT_RENDER, [$this, 'registerTemplateStrategy']);
        $eventManager->attach(MvcEvent::EVENT_ROUTE, array($this, 'setTranslators'));
        $eventManager->attach(MvcEvent::EVENT_RENDER, array($this, 'forceStrictVars'));
        $eventManager->attach(MvcEvent::EVENT_DISPATCH_ERROR, array($this, 'onError'));
        $eventManager->attach(MvcEvent::EVENT_RENDER_ERROR, array($this, 'onError'));
    }

    /**
     * Register template strategy.
     */
    public function registerTemplateStrategy(MvcEvent $e)
    {
        $serviceManager = $e->getApplication()->getServiceManager();
        /** @var View */
        $view = $serviceManager->get(View::class);
        /** @var TemplateStrategy */
        $templateStategy = $serviceManager->get(TemplateStrategy::class);
        $templateStategy->attach($view->getEventManager(), 100);
        $routeMatch = $e->getRouteMatch();
        if ($routeMatch) {
            $templateStategy->currentAction = $routeMatch->getParam('action');
        }
    }

    /**
     * Hook to set/disable translators
     *
     * This is invoked by the "route" event to avoid invocation of factories
     * within the bootstrap event which would cause problems for testing.
     */
    public function setTranslators(MvcEvent $e)
    {
        $serviceManager = $e->getApplication()->getServiceManager();
        $helperPluginManager = $serviceManager->get('ViewHelperManager');

        $translator = $serviceManager->get(Translator::class);
        AbstractValidator::setDefaultTranslator($translator);

        // Disable translations in the FormElementErrors view helper. Otherwise
        // it would try to translate the already translated messages from the
        // validators. Translation must be left to the validators because
        // placeholders in message templates can only be handled there.
        $helperPluginManager->get(FormElementErrors::class)->setTranslateMessages(false);

        // Disable translations in the FlashMessenger view helper. Messages are
        // already translated in controllers to enable string formatting with
        // placeholders.
        $helperPluginManager->get(FlashMessenger::class)->setTranslatorEnabled(false);
    }

    /**
     * Hook to trigger notices on undefined view variables
     *
     * This is invoked by the "render" event.
     *
     * @param \Laminas\Mvc\MvcEvent $e MVC event
     */
    public function forceStrictVars(\Laminas\EventManager\EventInterface $e)
    {
        $this->setStrictVars($e->getViewModel());
    }

    /**
     * Set strict vars on a view model recursively
     *
     * @param \Laminas\View\Model\ViewModel $model
     */
    protected function setStrictVars(\Laminas\View\Model\ViewModel $model)
    {
        $vars = $model->getVariables();
        if (!$vars instanceof \Laminas\View\Variables) {
            /** @psalm-suppress InvalidArgument if this is called, wo apparently have an array */
            $vars = new \Laminas\View\Variables($vars);
        }
        $vars->setStrictVars(true);
        $model->setVariables($vars, true);
        foreach ($model->getChildren() as $child) {
            $this->setStrictVars($child);
        }
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
