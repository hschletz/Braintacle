<?php

/**
 * Controller for all software-related actions.
 *
 * Copyright (C) 2011-2022 Holger Schletz <holger.schletz@web.de>
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
class SoftwareController extends \Laminas\Mvc\Controller\AbstractActionController
{
    /**
     * Software manager
     * @var \Model\SoftwareManager
     */
    protected $_softwareManager;

    /**
     * Form manager
     * @var \Laminas\Form\FormElementManager
     */
    protected $_formManager;

    /**
     * Filter to fix incorrectly encoded names
     * @var \Library\Filter\FixEncodingErrors
     */
    protected $_fixEncodingErrors;

    /**
     * Constructor
     *
     * @param \Model\SoftwareManager $softwareManager
     * @param \Laminas\Form\FormElementManager $formManager
     * @param \Library\Filter\FixEncodingErrors $fixEncodingErrors
     */
    public function __construct(
        \Model\SoftwareManager $softwareManager,
        \Laminas\Form\FormElementManager $formManager,
        \Library\Filter\FixEncodingErrors $fixEncodingErrors
    ) {
        $this->_softwareManager = $softwareManager;
        $this->_formManager = $formManager;
        $this->_fixEncodingErrors = $fixEncodingErrors;
    }

    /**
     * Display filter and software forms according to selected filter (default: accepted)
     *
     * @return array filter, software[], order, filterForm, softwareForm
     */
    public function indexAction()
    {
        $filter = $this->params()->fromQuery('filter', 'accepted');
        $order = $this->getOrder('name');

        $software = $this->_softwareManager->getSoftware(
            array(
                'Os' => $this->params()->fromQuery('os', 'windows'),
                'Status' => $filter,
            ),
            $order['order'],
            $order['direction']
        )->toArray();

        $filterForm = $this->_formManager->get('Console\Form\SoftwareFilter');
        $filterForm->setFilter($filter);

        $softwareForm = $this->_formManager->get('Console\Form\Software');
        $softwareForm->setSoftware($software);

        $session = new \Laminas\Session\Container('ManageSoftware');
        $session->filter = $filter;

        return array(
            'filterForm' => $filterForm,
            'softwareForm' => $softwareForm,
            'software' => $software,
            'order' => $order,
            'filter' => $filter,
        );
    }

    /**
     * Confirm software definition actions
     *
     * @return array|\Laminas\Http\Response array(software, display)
     */
    public function confirmAction()
    {
        $post = $this->params()->fromPost();

        if (isset($post['Accept']) or isset($post['Ignore'])) {
            $session = new \Laminas\Session\Container('ManageSoftware');
            $form = $this->_formManager->get('Console\Form\Software');
            $form->setData($post);
            if ($form->isValid()) {
                $software = $form->getData()['Software'];
                if ($software) {
                    $session->setExpirationHops(1);
                    $session['software'] = array();
                    foreach ($software as $name => $value) {
                        $session['software'][] = base64_decode($name);
                    }
                    $session['display'] = isset($post['Accept']);

                    $vars = $session->getArrayCopy();
                    foreach ($vars['software'] as &$name) {
                        $name = $this->_fixEncodingErrors->filter($name);
                    }
                    return $vars;
                }
            }
            return $this->redirectToRoute('software', 'index', array('filter' => $session['filter']));
        } else {
            $response = $this->getResponse();
            $response->setStatusCode(400);
            return $response;
        }
    }

    /**
     * Accept/Ignore software definitions
     *
     * @return \Laminas\Http\Response
     */
    public function manageAction()
    {
        $post = $this->params()->fromPost();
        $session = new \Laminas\Session\Container('ManageSoftware');

        if (isset($post['no'])) {
            return $this->redirectToRoute('software', 'index', array('filter' => $session['filter']));
        } elseif (isset($post['yes'])) {
            $software = $session['software'];
            if ($software) {
                foreach ($software as $name) {
                    $this->_softwareManager->setDisplay($name, $session['display']);
                }
            }
            return $this->redirectToRoute('software', 'index', array('filter' => $session['filter']));
        } else {
            $response = $this->getResponse();
            $response->setStatusCode(400);
            return $response;
        }
    }
}
