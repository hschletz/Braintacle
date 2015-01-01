<?php
/**
 * Controller for managing preferences
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

class PreferencesController extends Zend_Controller_Action
{

    public function indexAction()
    {
        $this->_setParam('action', 'display'); // This will highlight the 'display' navigation item
        $this->_forward('display');
    }

    public function displayAction()
    {
        $this->_useForm('Form_Preferences_Display');
    }

    public function inventoryAction()
    {
        $this->_useForm('Form_Preferences_Inventory');
    }

    public function agentAction()
    {
        $this->_useForm('Form_Preferences_Agent');
    }

    public function packagesAction()
    {
        $this->_useForm('Form_Preferences_Packages');
    }

    public function downloadAction()
    {
        $this->_useForm('Form_Preferences_Download');
    }

    public function networkscanningAction()
    {
        $this->_useForm('Form_Preferences_NetworkScanning');
    }

    public function groupsAction()
    {
        $this->_useForm('Form_Preferences_Groups');
    }

    public function rawdataAction()
    {
        $this->_useForm('Form_Preferences_RawData');
    }

    public function filtersAction()
    {
        $this->_useForm('Form_Preferences_Filters');
    }

    public function systemAction()
    {
        $this->_useForm('Form_Preferences_System');
    }

    public function userdefinedAction()
    {
        $form = new Form_DefineFields;
        if ($this->getRequest()->isPost() and $form->isValid($_POST)) {
            $form->process();
            $this->_helper->redirector('userdefined', 'preferences');
        } else {
            // render form
            $this->view->form = $form;
        }
    }

    public function deletefieldAction()
    {
        $form = new Form_YesNo;
        $field = $this->_getParam('name');
        if ($this->getRequest()->isPost()) {
            if ($form->isValid($_POST) and $this->_getParam('yes')) {
                Model_UserDefinedInfo::deleteField($field);
            }
            $this->_helper->redirector('userdefined', 'preferences');
        } else {
            // render confirmation form
            $this->view->form = $form;
            $this->view->field = $field;
        }
    }

    public function networkdevicesAction()
    {
        $form = new Form_ManageNetworkDeviceTypes;
        if ($this->getRequest()->isPost() and $form->isValid($_POST)) {
            $form->process();
            $this->_helper->redirector('index', 'network');
        } else {
            // render form
            $this->view->form = $form;
        }
    }

    public function deletedevicetypeAction()
    {
        $form = new Form_YesNo;
        $type = Model_NetworkDeviceType::construct($this->_getParam('id'));
        if ($this->getRequest()->isPost()) {
            if ($form->isValid($_POST) and $this->_getParam('yes')) {
                $type->delete();
            }
            $this->_helper->redirector('networkdevices', 'preferences');
        } else {
            // render confirmation form
            $this->view->form = $form;
            $this->view->type = $type;
        }
    }

    public function registryvaluesAction()
    {
        $form = new Form_ManageRegistryValues;
        if ($this->getRequest()->isPost() and $form->isValid($_POST)) {
            $form->process();
            $form->resetNewValue();
        }
        // render form
        $this->view->form = $form;
    }

    public function deleteregistryvalueAction()
    {
        $form = new Form_YesNo;
        $value = Model_RegistryValue::construct($this->_getParam('id'));
        if ($this->getRequest()->isPost()) {
            if ($form->isValid($_POST) and $this->_getParam('yes')) {
                $value->delete();
            }
            $this->_helper->redirector('registryvalues', 'preferences');
        } else {
            // render confirmation form
            $this->view->form = $form;
            $this->view->value = $value;
        }
    }

    /**
     * Standard preferences handling via Form_Preferences subclass
     * @param string $class Name of the form class
     */
    protected function _useForm($class)
    {
        $form = new $class;
        if ($this->getRequest()->isGet()) {
            $form->loadDefaults();
        } else {
            $form->process($_POST);
        }
        $this->view->form = $form;
        // Use generic view script instead of a bunch of individual, but
        // identical scripts.
        $this->_helper->viewRenderer->renderScript('preferences/form.phtml');
    }

}

