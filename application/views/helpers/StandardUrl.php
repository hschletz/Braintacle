<?php
/**
 * Render application URL with standard parameter syntax
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
 * @package ViewHelpers
 */
/**
 * Render application URL with standard parameter syntax
 * @package ViewHelpers
 */
class Zend_View_Helper_StandardUrl extends Zend_View_Helper_Abstract
{

    /**
     * Render application url with standard parameter syntax
     *
     * This is similar to the 'Url' helper, except that parameters are appended
     * using standard syntax ('...?param1=value1&...') instead of ZF conventions
     * (.../param1/value1/...). This avoids problems with encoded slashes which
     * would be affected by Apache's AllowEncodedSlashes directive. The
     * controller and action (if given) are assembled as usual, i.e. not as an
     * URL parameter.
     *
     * @param array $params URL parameters, unencoded
     * @param bool $inheritParams Inherit GET parameters from current request. Parameters from $params take precedence.
     * @return string URL
     */
    function standardUrl($params, $inheritParams = false)
    {
        if ($inheritParams) {
            $request = Zend_Controller_Front::getInstance()->getRequest();
            // Append missing GET parameters
            $params += $request->getQuery();
            // Append controller and action if still missing
            if (!isset($params['controller'])) {
                $params['controller'] = $request->getControllerName();
            }
            if (!isset($params['action'])) {
                $params['action'] = $request->getActionName();
            }
        }
        // Separate controller and action from other parameters
        $route = array();
        if (isset($params['controller'])) {
            $route['controller'] = $params['controller'];
            unset($params['controller']);
        }
        if (isset($params['action'])) {
            $route['action'] = $params['action'];
            unset($params['action']);
        }
        // Construct URL without parameters
        $url = $this->view->Url($route, null, false, true);
        // Append parameters
        $delimiter = '?';
        foreach ($params as $param => $value) {
            $url .= $delimiter . urlencode($param) . '=' . urlencode($value);
            $delimiter = '&';
        }
        return $this->view->escape($url); // Encodes ampersands
    }

}
