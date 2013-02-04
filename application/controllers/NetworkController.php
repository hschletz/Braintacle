<?php
/**
 * Controller for subnets and IP discovery
 *
 * $Id$
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

class NetworkController extends Zend_Controller_Action
{

    public function preDispatch()
    {
        $action = $this->_getParam('action');
        if ($action != 'index') {
            Zend_Registry::set('subNavigation', 'Inventory');
        }
    }

    public function indexAction()
    {
        $ordering = $this->_helper->ordering('Name');
        $this->view->subnets = Model_Subnet::createStatementStatic(
            $ordering['order'],
            $ordering['direction']
        );
        $this->view->devices = Model_NetworkDeviceType::createStatementStatic();
    }

    public function showidentifiedAction()
    {
        $ordering = $this->_helper->ordering('DiscoveryDate', 'desc');
        $this->view->devices = Model_NetworkDevice::getDevices(
            $this->_getParam('subnet'),
            $this->_getParam('mask'),
            true,
            $ordering['order'],
            $ordering['direction']
        );
    }

    public function showunknownAction()
    {
        $ordering = $this->_helper->ordering('DiscoveryDate', 'desc');
        $this->view->devices = Model_NetworkDevice::getDevices(
            $this->_getParam('subnet'),
            $this->_getParam('mask'),
            false,
            $ordering['order'],
            $ordering['direction']
        );
    }

    public function propertiesAction()
    {
        $subnet = Model_Subnet::construct(
            $this->_getParam('subnet'),
            $this->_getParam('mask')
        );

        $form = new Form_Subnet;
        if ($this->getRequest()->isPost()) {
            if ($form->isValid($_POST)) {
                $subnet->setName($form->getValue('Name'));
                $this->redirect('network');
            }
        } else {
            $form->setValuesFromSubnet($subnet);
        }

        $this->view->subnet = $subnet;
        $this->view->form = $form;
    }

    public function editAction()
    {
        $device = Model_NetworkDevice::getByMacAddress($this->_getParam('macaddress'));
        if ($device) {
            $form = new Form_NetworkDevice;

            if ($this->getRequest()->isGet()) {
                // Initialize form upon first usage.
                $form->setValuesFromDevice($device);
                $this->view->form = $form;
                $this->view->device = $device;
                return;
            }

            if ($form->isValid($_POST)) {
                $device->fromArray($form->getValues());
                $device->save();
            } else {
                $this->view->form = $form;
                $this->view->device = $device;
                return;
            }
        }
        $this->redirect('network');
    }

    public function deleteAction()
    {
        $device = Model_NetworkDevice::getByMacAddress($this->_getParam('macaddress'));
        if ($device) {
            if ($this->getRequest()->isGet()) {
                $this->view->device = $device;
                return; // proceed with view script
            }
            if ($this->_getParam('yes')) {
                $device->delete();
            }
        }

        $this->redirect('network');
    }

}

