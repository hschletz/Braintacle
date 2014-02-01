<?php
/**
 * Controller for managing Braintacle user accounts
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
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 */

namespace Console\Controller;

/**
 * Controller for managing Braintacle user accounts
 */
class AccountsController extends \Zend\Mvc\Controller\AbstractActionController
{
    /**
     * Operator prototype
     * @var \Model_Account
     */
    protected $_operators;

    /**
     * Account creation form
     * @var \Form_Account_New
     */
    protected $_formAccountNew;

    /**
     * Account edit form
     * @var \Form_Account_Edit
     */
    protected $_formAccountEdit;

    /**
     * Constructor
     *
     * @param \Model_Account $operators operator prototype
     * @param \Form_Account_New $formAccountNew Account creation form
     * @param \Form_Account_Edit $formAccountEdit Account edit form
     */
    public function __construct(
        \Model_Account $operators,
        \Form_Account_New $formAccountNew,
        \Form_Account_Edit $formAccountEdit
    )
    {
        $this->_operators = $operators;
        $this->_formAccountNew = $formAccountNew;
        $this->_formAccountEdit = $formAccountEdit;
    }

    /**
     * Display overview of operators
     *
     * @return array operators, order, direction, identity
     */
    public function indexAction()
    {
        $response = $this->getOrder('Id');
        $response['accounts'] = $this->_operators->fetchAll(
            $response['order'],
            $response['direction']
        );
        $response['identity'] = $this->_operators->getAuthService()->getIdentity();
        return $response;
    }

    /**
     * Create account
     *
     * @return array|\Zend\Http\Response Array (form) or redirect response
     */
    public function addAction()
    {
        $form = $this->_formAccountNew;

        if ($this->getRequest()->isPost() and $form->isValid($this->params()->fromPost())) {
            $data = $form->getValues();
            $this->_operators->create($data, $data['Password']);
            return $this->redirectToRoute('accounts', 'index');
        }

        $this->setActiveMenu('Preferences', 'Users');
        return array ('form' => $form);
    }


    /**
     * Edit account
     *
     * @return array|\Zend\Http\Response Array (form) or redirect response
     */
    public function editAction()
    {
        $form = $this->_formAccountEdit;

        if ($this->getRequest()->isPost() and $form->isValid($this->params()->fromPost())) {
            $data = $form->getValues();
            $this->_operators->update($data['OriginalId'], $data, $data['Password']);
            return $this->redirectToRoute('accounts', 'index');
        }

        $this->setActiveMenu('Preferences', 'Users');
        $form->setId($this->params()->fromQuery('id'));
        return array ('form' => $form);
    }

    /**
     * Delete account
     *
     * @return array|\Zend\Http\Response Array (id) or redirect response
     */
    public function deleteAction()
    {
        $id = $this->params()->fromQuery('id');

        if ($this->getRequest()->isPost()) {
            if ($this->params()->fromPost('yes')) {
                $this->_operators->delete($id);
            }
            $this->redirectToRoute('accounts', 'index');
        } else {
            $this->setActiveMenu('Preferences', 'Users');
            return array('id' => $id);
        }
    }
}
