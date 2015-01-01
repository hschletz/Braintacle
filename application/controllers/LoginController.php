<?php
/**
 * Controller for all login-related actions.
 *
 * Copyright (C) 2011-2015 Holger Schletz <holger.schletz@web.de>
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

class LoginController extends Zend_Controller_Action
{

    public function indexAction()
    {
        $this->view->form = new Form_Login;
    }

    public function preDispatch()
    {
        if (Zend_Auth::getInstance()->hasIdentity()) {
            // Don't show the login form if the user is already logged in
            if ($this->getRequest()->getActionName() != 'logout') {
                $this->_helper->redirector('index', 'computer');
            }
        } else {
            // Logout action is only available to logged in users.
            // Redirect to the login form otherwise.
            if ($this->getRequest()->getActionName() == 'logout') {
                $this->_helper->redirector('index');
            }
        }
    }

    public function loginAction()
    {
        $request = $this->getRequest();
        // Login via GET is not supported
        if (!$request->isPost()) {
            return $this->_helper->redirector('index');
        }

        // Validate form
        $form = new Form_Login;
        if (!$form->isValid($request->getPost())) {
            // Invalid entries
            $this->view->form = $form;
            return $this->render('index'); // re-render login form
        }

        // Check credentials
        if (!Model_Account::login($form->getValue('userid'), $form->getValue('password'))) {
            $form->setDescription('Invalid username or password');
            $this->view->form = $form;
            return $this->render('index'); // re-render login form
        }

        // Authentication successful.
        // Redirect to computer listing as long there is nothing interesting for a start page.
        $this->_helper->redirector('index', 'computer');
    }

    public function logoutAction()
    {
        Zend_Auth::getInstance()->clearIdentity();
        $this->_helper->redirector('index');
    }

}
