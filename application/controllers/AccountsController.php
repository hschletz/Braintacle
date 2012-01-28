<?php
/**
 * Controller for managing Braintacle user accounts
 *
 * $Id$
 *
 * Copyright (C) 2011,2012 Holger Schletz <holger.schletz@web.de>
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

class AccountsController extends Zend_Controller_Action
{

    public function indexAction()
    {
        $this->_helper->ordering('Id');
        $this->view->accounts = Model_Account::createStatementStatic(
            $this->view->order,
            $this->view->direction
        );
    }

    public function addAction()
    {
        $form = new Form_Account;

        if ($this->getRequest()->isPost() and $form->isValid($_POST)) {
            $data = $form->getValues();
            Model_Account::create($data, $data['Password']);
            $this->_redirect('accounts');
        }
        $this->view->form = $form;
    }

}