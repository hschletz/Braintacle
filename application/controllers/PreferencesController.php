<?php
/**
 * Controller for managing preferences
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
            $this->_redirect('preferences/userdefined');
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
            $this->_redirect('preferences/userdefined');
        } else {
            // render confirmation form
            $this->view->form = $form;
            $this->view->field = $field;
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

