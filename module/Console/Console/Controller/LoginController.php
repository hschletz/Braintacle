<?php
/**
 * Controller for all login-related actions.
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

namespace Console\Controller;

use \Zend\View\Model\ViewModel;

/**
 * Controller for all login-related actions.
 */
class LoginController extends \Zend\Mvc\Controller\AbstractActionController
{
    /**
     * Alias for loginAction()
     */
    public function indexAction()
    {
        $response = $this->loginAction();
        if ($response instanceof ViewModel) {
            $response->setTemplate('console/login/login');
        }
        return $response;
    }

    /**
     * Handle login form
     */
    public function loginAction()
    {
        $auth = $this->getServiceLocator()->get('Library\AuthenticationService');

        // Don't show the login form if the user is already logged in
        if ($auth->hasIdentity()) {
            return $this->redirectToRoute('computer');
        }

        $form = new \Form_Login;
        $form->setAction($this->urlFromRoute('login', 'login'));

        $request = $this->getRequest();
        if ($request->isPost() and $form->isValid($request->getPost()->toArray())) {
            // Check credentials
            if ($auth->login($form->getValue('userid'), $form->getValue('password'))) {
                // Authentication successful. Redirect to computer listing.
                return $this->redirectToRoute('computer');
            } else {
                $form->setDescription('Invalid username or password');
            }
        }

        // Manual setup of ViewModel because indexAction might have to modify it
        $viewModel = new ViewModel;
        $viewModel->form = $form;
        return $viewModel;
    }

    /**
     * Log out and get back to login form
     */
    public function logoutAction()
    {
        $this->getServiceLocator()->get('Library\AuthenticationService')->clearIdentity();
        return $this->redirectToRoute('login', 'login');
    }
}
