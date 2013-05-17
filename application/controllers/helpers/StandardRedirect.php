<?php
/**
 * Redirect to a given action with standard URL parameter syntax
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
 *
 * @package ActionHelpers
 */
/**
 * Redirect to a given action with standard URL parameter syntax
 * @package ActionHelpers
 */
class Zend_Controller_Action_Helper_StandardRedirect
    extends Zend_Controller_Action_Helper_Abstract
{

    /**
     * Redirect to a given action with standard URL parameter syntax
     *
     * This is similar to the 'Redirector' helper, but the URL is constructed
     * with standard syntax ('...?param1=value1&...') instead of ZF conventions
     * (.../param1/value1/...). This avoids problems with encoded slashes which
     * would be affected by Apache's AllowEncodedSlashes directive. The module,
     * controller and action (if given) are assembled as usual, i.e. not as an
     * URL parameter.
     *
     * @param string $action Action name
     * @param string $controller Controller name. Default: inherit from current request
     * @param string $module Module name. Default: inherit from current request
     * @param array $params URL parameters, unencoded
     * @return string URL
     */
    function direct($action, $controller = null, $module = null, array $params = null)
    {
        $actionController = $this->getActionController();
        $url = $actionController->getHelper('Url')->simple(
            $action,
            $controller,
            $module
        );
        // Append parameters
        $delimiter = '?';
        foreach ($params as $param => $value) {
            $url .= $delimiter . urlencode($param) . '=' . urlencode($value);
            $delimiter = '&';
        }
        $actionController->getHelper('Redirector')->gotoUrl(
            $url,
            array('prependBase' => false)
        );
    }

}
