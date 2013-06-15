<?php
/**
 * Route for standard URL parameter syntax
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
 * @package Library
 */
/**
 * Route for standard URL parameter syntax
 *
 * This is similar to the 'Module' route, except that URLs are assembled using
 * standard syntax ('...?param1=value1&...') instead of ZF conventions
 * (.../param1/value1/...). This avoids problems with encoded slashes which
 * would be affected by Apache's AllowEncodedSlashes directive. The module,
 * controller and action (if given) are assembled as usual, i.e. not as an URL
 * parameter.
 *
 * Only parameters that are passed explicitly to the assemble() method are
 * evaluated. Other methods like providing/unsetting default parameters are not
 * supported.
 *
 * @package Library
 */
class Braintacle_Controller_Router_Route_Module extends Zend_Controller_Router_Route_Module
{
    /** @ignore */
    public function assemble($data = array(), $reset = false, $encode = true, $partial = false)
    {
        // Move module, controller and action from $data to $route and use
        // $route to construct the URL without parameters.
        $route = array();
        if (isset($data[$this->_moduleKey])) {
            $route[$this->_moduleKey] = $data[$this->_moduleKey];
            unset($data[$this->_moduleKey]);
        }
        if (isset($data[$this->_controllerKey])) {
            $route[$this->_controllerKey] = $data[$this->_controllerKey];
            unset($data[$this->_controllerKey]);
        }
        if (isset($data[$this->_actionKey])) {
            $route[$this->_actionKey] = $data[$this->_actionKey];
            unset($data[$this->_actionKey]);
        }
        $url = parent::assemble($route, $reset, $encode, $partial);

        // Append parameters
        $delimiter = '?';
        foreach ($data as $param => $value) {
            $url .= $delimiter;
            $url .= $encode ? urlencode($param) : $param;
            $url .= '=';
            $url .= $encode ? urlencode($value) : $value;
            $delimiter = '&';
        }

        return $url;
    }
}
