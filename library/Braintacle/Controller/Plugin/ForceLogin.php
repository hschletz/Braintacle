<?php
/**
 * Redirect unauthenticated requests to login page
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
 * Redirect unauthenticated requests to login page
 * @package Library
 */
class Braintacle_Controller_Plugin_ForceLogin extends Zend_Controller_Plugin_Abstract
{
    /** @ignore */
    public function routeShutdown(Zend_Controller_Request_Abstract $request)
    {
        // If user is not yet authenticated, redirect to the login page except
        // for the login controller, in which case redirection would result in
        // an infinite loop. LoginController will handle the rest.
        if (!Zend_Auth::getInstance()->hasIdentity() and
            $request->getControllerName() != 'login'
        ) {
            $redirector = new Zend_Controller_Action_Helper_Redirector();
            $redirector->gotoSimpleAndExit('index', 'login');
        }
    }
}
