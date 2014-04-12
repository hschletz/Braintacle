<?php
/**
 * Controller for all software-related actions.
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
 * 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

namespace Console\Controller;

/**
 * Controller for all software-related actions.
 */
class SoftwareController extends \Zend\Mvc\Controller\AbstractActionController
{
    /**
     * \Model_Software prototype
     * @var \Model_Software
     */
    protected $_software;

    /**
     * Software filter form
     * @var \Form_SoftwareFilter
     */
    protected $_form;

    /**
     * Constructor
     *
     * @param \Model_Software $software
     * @param \Form_SoftwareFilter $form
     */
    public function __construct(\Model_Software $software, \Form_SoftwareFilter $form)
    {
        $this->_software = $software;
        $this->_form = $form;
    }

    /**
     * Display filter form and all software according to selected filter (default: accepted)
     *
     * @return array filter, form, software[]
     */
    public function indexAction()
    {
        $filter = $this->params()->fromQuery('filter', 'accepted');
        $this->_form->setFilter($filter); // invalid filter will trigger an exception
        $session = new \Zend\Session\Container('ManageSoftware');
        $session->filter = $filter;

        $order = $this->getOrder('Name');
        return array(
            'filter' => $filter,
            'form' => $this->_form,
            'software' => $this->_software->find(
                array ('Name', 'NumComputers'),
                $order['order'],
                $order['direction'],
                array(
                    'Os' => $this->params()->fromQuery('os', 'windows'),
                    'Status' => $filter,
                    'Unique' => null,
                )
            ),
            'order' => $order,
        );
    }

    /**
     * Ignore selected software
     *
     * @return mixed array(name) or redirect response
     */
    public function ignoreAction()
    {
        return $this->_manage('ignore');
    }

    /**
     * Accept selected software
     *
     * @return mixed array(name) or redirect response
     */
    public function acceptAction()
    {
        return $this->_manage('accept');
    }

    /**
     * Accept or ignore selected software
     *
     * @param string $action Method to call on \Model_Software, must be 'accept' or 'ignore'
     * @return mixed array(name) or redirect response
     */
    protected function _manage($action)
    {
        $name = $this->params()->fromQuery('name');
        if ($name === null) {
            throw new \RuntimeException('Missing name parameter');
        }
        if ($this->getRequest()->isGet()) {
            return array('name' => $name); // Display confirmation form
        } else {
            if ($this->params()->fromPost('yes')) {
                $this->_software->$action($name); // accept/ignore software
            }
            $session = new \Zend\Session\Container('ManageSoftware');
            return $this->redirectToRoute('software', 'index', array('filter' => $session->filter));
        }
    }
}
