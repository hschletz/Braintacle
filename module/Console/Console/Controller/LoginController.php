<?php
/**
 * Controller for all login-related actions.
 *
 * Copyright (C) 2011-2014 Holger Schletz <holger.schletz@web.de>
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
     * Authentication service
     * @var \Library\Authentication\AuthenticationService
     */
    protected $_authenticationService;

    /**
     * Login form
     * @var \Form_Login
     */
    protected $_form;

    /**
     * Constructor
     *
     * @param \Library\Authentication\AuthenticationService $authenticationService Authentication service
     */
    public function __construct(
        \Library\Authentication\AuthenticationService $authenticationService,
        \Form_Login $form
    )
    {
        $this->_authenticationService = $authenticationService;
        $this->_form = $form;
    }

    /**
     * Redirect to login action
     *
     * @return \Zend\Http\Response Redirect response
     */
    public function indexAction()
    {
        return $this->redirectToRoute('login', 'login');
    }

    /**
     * Handle login form
     *
     * @return array|\Zend\Http\Response array (form => \Form_Login) or redirect response
     */
    public function loginAction()
    {
        // Don't show the login form if the user is already logged in
        if ($this->_authenticationService->hasIdentity()) {
            return $this->redirectToRoute('computer');
        }

        $this->_form->setAction($this->urlFromRoute('login', 'login'));

        if ($this->getRequest()->isPost() and $this->_form->isValid($this->params()->fromPost())) {
            // Check credentials
            if (
                $this->_authenticationService->login(
                    $this->_form->getValue('userid'),
                    $this->_form->getValue('password')
                )
            ) {
                // Authentication successful. Redirect to appropriate page.
                $session = new \Zend\Session\Container('login');
                if (isset($session->originalUri)) {
                    // We got redirected here from another page. Redirect to original page.
                    $response = $this->redirect()->toUrl($session->originalUri);
                } else {
                    // Redirect to default page (computer listing)
                    $response = $this->redirectToRoute('computer');
                }
                $session->getManager()->getStorage()->clear('login');
                return $response;
            } else {
                $this->_form->setDescription('Invalid username or password');
            }
        }
        return array('form' => $this->_form);
    }

    /**
     * Log out and get back to login form
     *
     * @return \Zend\Http\Response Redirect response
     */
    public function logoutAction()
    {
        $this->_authenticationService->clearIdentity();
        return $this->redirectToRoute('login', 'login');
    }
}
