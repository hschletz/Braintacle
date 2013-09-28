<?php
/**
 * Generate URL to given controller and action
 *
 * Copyright (C) 2011-2013 Holger Schletz <holger.schletz@web.de>
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

/**
 * Generate URL to given controller and action
 */
class ConsoleUrl extends \Zend\View\Helper\AbstractHelper
{
    /**
     * Generate URL to given controller and action
     *
     * @param string $controller Controller name
     * @param string $action Action name
     * @return string Target URL
     */
    public function __invoke($controller, $action)
    {
        return $this->view->url(
            'console',
            array(
                'controller' => $controller,
                'action' => $action,
            )
        );
    }
}
