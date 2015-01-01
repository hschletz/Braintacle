<?php
/**
 * Controller for managing duplicate computers
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

class DuplicatesController extends Zend_Controller_Action
{

    public function indexAction()
    {
        $duplicates = array();
        foreach (array('Name', 'MacAddress', 'Serial', 'AssetTag') as $criteria) {
            $num = Model_Computer::findDuplicates($criteria, true);
            if ($num) {
                $duplicates[$criteria] = $num;
            }
        }
        $this->view->duplicates = $duplicates;
    }

    public function showAction()
    {
        Zend_Registry::set('subNavigation', 'Inventory');
        $this->_helper->ordering('Id', 'asc');

        $this->view->computers = Model_Computer::findDuplicates(
            $this->_getParam('criteria'),
            false,
            $this->view->order,
            $this->view->direction
        );
        $this->view->criteria = $this->_getParam('criteria');
    }

    public function mergeAction()
    {
        Model_Computer::mergeComputers(
            $this->_getParam('computers'),
            $this->_getParam('mergeUserdefined'),
            $this->_getParam('mergeGroups'),
            $this->_getParam('mergePackages')
        );
        $this->_helper->redirector('index', 'duplicates');
    }

    public function allowAction()
    {
        $criteria = $this->_getParam('criteria');
        $value = $this->_getParam('value');
        $form = new Form_YesNo;

        if ($this->getRequest()->isPost()) {
            if ($form->isValid($_POST) and $this->_getParam('yes')) {
                Model_Computer::allowDuplicates($criteria, $value);
            }
            $this->_helper->redirector('index', 'duplicates');
        } else {
            $this->view->form = $form;
            $this->view->criteria = $criteria;
            $this->view->value = $value;
        }
    }

}

