<?php
/**
 * Controller for all software-related actions.
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

class SoftwareController extends Zend_Controller_Action
{

    public function indexAction()
    {
        // Get operating system for which to display software
        $os = $this->_getParam('os', 'windows');

        // Get filter (accepted|ignored|new|all)
        $filter = $this->_getParam('filter', 'accepted');
        $this->view->filter = $filter;

        // Create form. Invalid filter will trigger an exception.
        $form = new Form_SoftwareFilter;
        $form->setFilter($filter);
        $this->view->form = $form;

        // Store filter in session to make the form redirect with the same
        // filter applied.
        $session = new Zend_Session_Namespace('ManageSoftware');
        $session->filter = $filter;

        // Create statement
        $ordering = $this->_helper->ordering('Name');
        $software = new Model_Software;
        $this->view->software = $software->createStatement(
            array ('Name', 'NumComputers'),
            $ordering['order'],
            $ordering['direction'],
            array(
                'Os' => $os,
                'Status' => $filter,
                'Unique' => null,
            )
        );
    }

    public function ignoreAction()
    {
        $this->_manage('ignore');
    }

    public function acceptAction()
    {
        $this->_manage('accept');
    }

    protected function _manage($action)
    {
        $name = $this->_getParam('name');

        if ($this->getRequest()->isGet()) {
            // Display form
            $this->view->name = $name;
            return;
        } else {
            // Evaluate form
            if ($this->_getParam('yes')) {
                // 'yes' was clicked: Accept or ignore software
                call_user_func(array('Model_Software', $action), $name);
            }
            // else 'no' was clicked: do nothing, just redirect
        }

        // Retrieve filter from session and redirect with the filter applied
        $session = new Zend_Session_Namespace('ManageSoftware');
        $this->_redirect('software?filter=' . $session->filter);
    }

}


