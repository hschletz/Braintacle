<?php

/**
 * The Console module
 *
 * Copyright (C) 2011-2025 Holger Schletz <holger.schletz@web.de>
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
        $eventManager->attach(MvcEvent::EVENT_RENDER, array($this, 'forceStrictVars'));
    }

    /**
     * Hook to trigger notices on undefined view variables
     *
     * This is invoked by the "render" event.
     *
     * @param \Laminas\Mvc\MvcEvent $e MVC event
     *
     * @psalm-suppress PossiblyUnusedMethod
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
