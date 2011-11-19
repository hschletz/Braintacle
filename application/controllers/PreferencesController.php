<?php
/**
 * Controller for managing preferences
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
    }

}

