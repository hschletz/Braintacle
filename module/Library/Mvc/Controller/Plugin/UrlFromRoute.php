<?php

/**
 * Build URL from standard route (controller/action)
 *
 * Copyright (C) 2011-2022 Holger Schletz <holger.schletz@web.de>
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

namespace Library\Mvc\Controller\Plugin;

use Laminas\Mvc\Controller\Plugin\Url;

/**
 * Build URL from standard route (controller/action)
 *
 * This is a convenience wrapper around Laminas' URL plugin. It is designed to
 * operate on standard routes (/module/controller/action).
 */
class UrlFromRoute extends \Laminas\Mvc\Controller\Plugin\AbstractPlugin
{
    private Url $urlPlugin;

    public function __construct(Url $urlPlugin)
    {
        $this->urlPlugin = $urlPlugin;
    }

    /**
     * Build URL
     *
     * All arguments get urlencode()d before being used. Calling code must not
     * encode any arguments.
     *
     * @param string $controllerName Controller name. If empty, the default controller is used.
     * @param string $action Action name. If empty, the default action is used.
     * @param array $params Associative array of URL parameters
     * @return string URL
     */
    public function __invoke($controllerName = null, $action = null, array $params = array())
    {
        $path = array();
        if ($controllerName) {
            $path['controller'] = urlencode($controllerName);
        }
        if ($action) {
            $path['action'] = urlencode($action);
        }
        return $this->urlPlugin->fromRoute(null, $path, ['query' => $params]);
    }
}
