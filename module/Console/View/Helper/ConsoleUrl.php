<?php

/**
 * Generate URL to given controller and action
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

namespace Console\View\Helper;

use Laminas\Http\Request;
use Laminas\Router\RouteMatch;
use Laminas\Router\RouteStackInterface;

/**
 * Generate URL to given controller and action
 *
 * @deprecated Define named route and use RouteHelper::getPathForRoute().
 */
class ConsoleUrl extends \Laminas\View\Helper\AbstractHelper
{
    public function __construct(
        private Request $request,
        private RouteStackInterface $router,
        private RouteMatch $routeMatch,
    ) {}

    /**
     * Generate URL to given controller and action
     *
     * @param string $controller Optional controller name (default: current controller)
     * @param string $action Optional action name (default: current action)
     * @param array $params Optional associative array of query parameters
     * @param bool $inheritParams Include request query parameters. Parameters in $params take precedence.
     * @return string Target URL
     */
    public function __invoke($controller = null, $action = null, $params = array(), $inheritParams = false)
    {
        $route = array();
        if ($controller) {
            $route['controller'] = $controller;
        }
        if ($action) {
            $route['action'] = $action;
        }

        if ($inheritParams) {
            // Merge current request parameters (parameters from $params take precedence)
            $params = array_merge(
                $this->request->getQuery()->toArray(),
                $params
            );
        }
        $options = array();
        if (!empty($params)) {
            // Cast values to string to support values that would otherwise get
            // ignored by http_build_query(), like objects implementing
            // __toString()
            foreach ($params as $name => $value) {
                $options['query'][$name] = (string) $value;
            }
        }

        $route = array_merge($this->routeMatch->getParams(), $route);
        $options['name'] = 'console';

        return (string) $this->router->assemble($route, $options);
    }
}
