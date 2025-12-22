<?php

/**
 * Controller for managing Braintacle user accounts
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
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 */

namespace Console\Controller;

use Braintacle\Legacy\Controller;
use Braintacle\Legacy\MvcEvent;
use Laminas\Authentication\AuthenticationServiceInterface;

/**
 * Controller for managing Braintacle user accounts
 */
class AccountsController extends Controller
{
    /**
     * Operator manager
     * @var \Model\Operator\OperatorManager
     */
    protected $_operatorManager;

    /**
     * Account creation form
     * @var \Console\Form\Account\Add
     */
    protected $_formAccountAdd;

    /**
     * Account edit form
     * @var \Console\Form\Account\Edit
     */
    protected $_formAccountEdit;

    /**
     * Constructor
     *
     * @param \Model\Operator\OperatorManager $operatorManager Operator manager
     * @param \Console\Form\Account\Add $formAccountAdd Account creation form
     * @param \Console\Form\Account\Edit $formAccountEdit Account edit form
     */
    public function __construct(
        private AuthenticationServiceInterface $authenticationService,
        \Model\Operator\OperatorManager $operatorManager,
        \Console\Form\Account\Add $formAccountAdd,
        \Console\Form\Account\Edit $formAccountEdit
    ) {
        $this->_operatorManager = $operatorManager;
        $this->_formAccountAdd = $formAccountAdd;
        $this->_formAccountEdit = $formAccountEdit;
    }

    public function onDispatch(MvcEvent $e)
    {
        $e->setParam('template', 'MainMenu/PreferencesMenuLayout.latte');
        $e->setParam('subMenuRoute', 'preferencesUsersList');

        return parent::onDispatch($e);
    }

    /**
     * Display overview of operators
     *
     * @return array operators, order, direction, identity
     */
    public function indexAction()
    {
        $response = $this->getOrder('Id');
        $response['accounts'] = $this->_operatorManager->getOperators(
            $response['order'],
            $response['direction']
        );
        $response['identity'] = $this->authenticationService->getIdentity();

        return $response;
    }

    /**
     * Create account
     *
     * @return array|\Laminas\Http\Response Array (form) or redirect response
     */
    public function addAction()
    {
        $form = $this->_formAccountAdd;

        if ($this->getRequest()->isPost()) {
            $form->setData($this->params()->fromPost());
            if ($form->isValid()) {
                $data = $form->getData();
                $this->_operatorManager->createOperator($data, $data['Password']);
                return $this->redirectToRoute('preferencesUsersList');
            }
        }

        return array('form' => $form);
    }

    /**
     * Edit account
     *
     * @return array|\Laminas\Http\Response Array (form) or redirect response
     */
    public function editAction()
    {
        $form = $this->_formAccountEdit;

        if ($this->getRequest()->isPost()) {
            $form->setData($this->params()->fromPost());
            if ($form->isValid()) {
                $data = $form->getData();
                $this->_operatorManager->updateOperator($data['OriginalId'], $data, $data['Password']);
                return $this->redirectToRoute('preferencesUsersList');
            }
        } else {
            $operator = $this->_operatorManager->getOperator($this->params()->fromQuery('id'));
            $data = $operator->getArrayCopy();
            $data['OriginalId'] = $data['Id'];
            $form->setData($data);
        }

        return array('form' => $form);
    }

    /**
     * Delete account
     *
     * @return array|\Laminas\Http\Response Array (id) or redirect response
     */
    public function deleteAction()
    {
        $id = $this->params()->fromQuery('id');

        if ($this->getRequest()->isPost()) {
            if ($this->params()->fromPost('yes')) {
                $this->_operatorManager->deleteOperator($id);
            }
            return $this->redirectToRoute('preferencesUsersList');
        } else {
            return array('id' => $id);
        }
    }
}
