<?php
/**
 * Controller for managing groups
 *
 * $Id$
 *
 * Copyright (C) 2011 Holger Schletz <holger.schletz@web.de>
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

class GroupController extends Zend_Controller_Action
{

    public function preDispatch()
    {
        // Fetch group with given ID for actions referring to a particular group
        if ($this->_getParam('action') == 'index') {
            return; // no specific group for this action
        }

        $group = Model_Group::fetchById($this->_getParam('id'));
        if ($group) {
            $this->group = $group;
            $this->view->group = $group;
        } else {
            $this->_redirect('group');
        }
    }

    public function indexAction()
    {
        $this->_helper->ordering('Name', 'asc');

        $columns = array('Name', 'CreationDate', 'Description');
        $this->view->groups = Model_Group::createStatementStatic(
            $columns,
            null,
            null,
            $this->view->order,
            $this->view->direction
        );
    }

    public function generalAction()
    {
    }

    public function membersAction()
    {
        $this->_helper->ordering('InventoryDate', 'desc');

        $this->view->columns = array(
            'Name',
            'UserName',
            'InventoryDate',
            'Membership',
        );
        $this->view->computers = Model_Computer::createStatementStatic(
            $this->view->columns,
            $this->view->order,
            $this->view->direction,
            'MemberOf',
            $this->group
        );
    }

    public function packagesAction()
    {
        $this->_helper->ordering('Name');
    }

    public function removepackageAction()
    {
        $session = new Zend_Session_Namespace('RemovePackageFromGroup');

        if ($this->getRequest()->isGet()) {
            $session->setExpirationHops(1);
            $session->packageName = $this->_getParam('name');
            $session->groupId = $this->_getParam('id');
            return; // proceed with view script
        }

        $id = $session->groupId;
        if ($this->_getParam('yes')) {
            $this->group->unaffectPackage($session->packageName);
        }

        $this->_redirect('group/packages/id/' . $id);
    }

    public function installpackageAction()
    {
        $group = $this->group;
        $form = new Form_AffectPackages;
        $form->addPackages($group);
        if ($form->isValid($_POST)) {
            $packages = array_keys($form->getValues());
            foreach ($packages as $packageName) {
                $group->installPackage($packageName);
            }
        }
        $this->_redirect('group/packages/id/' . $group->getId());
    }

    public function deleteAction()
    {
        $form = new Form_YesNo;

        if ($this->getRequest()->isPost()) {
            if ($form->isValid($_POST)) {
                if ($this->_getParam('yes')) {
                    $session = new Zend_Session_Namespace('GroupMessages');
                    $session->setExpirationHops(1);
                    $session->groupName = $this->group->getName();

                    if ($this->group->delete()) {
                        $session->success = true;
                        $session->message = $this->view->translate(
                            'Group \'%s\' was successfully deleted.'
                        );
                    } else {
                        $session->success = false;
                        $session->message = $this->view->translate(
                            'Group \'%s\' could not be deleted.'
                        );
                    }
                    $this->_redirect('group');
                } else {
                    $this->_redirect('group/general/id/' . $this->group->getId());
                }
            }
        } else {
            $this->view->form = $form;
        }
    }

}

