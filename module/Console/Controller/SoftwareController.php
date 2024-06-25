<?php

/**
 * Controller for all software-related actions.
 *
 * Copyright (C) 2011-2024 Holger Schletz <holger.schletz@web.de>
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

use Console\Form\SoftwareManagementForm;
use Console\Template\TemplateViewModel;
use Console\Validator\CsrfValidator;
use Laminas\Mvc\MvcEvent;
use Laminas\Session\Container;

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

    private SoftwareManagementForm $softwareManagementForm;

    public function __construct(
        \Model\SoftwareManager $softwareManager,
        SoftwareManagementForm $softwareManagementForm
    ) {
        $this->_softwareManager = $softwareManager;
        $this->softwareManagementForm = $softwareManagementForm;
    }

    public function onDispatch(MvcEvent $e)
    {
        $event = $this->getEvent();
        $event->setParam('template', 'InventoryMenuLayout.latte');
        $event->setParam('subMenuRoute', 'softwarePage');

        return parent::onDispatch($e);
    }

    /**
     * Display filter and software forms according to selected filter (default: accepted)
     */
    public function indexAction(): TemplateViewModel
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
        );

        $session = new \Laminas\Session\Container('ManageSoftware');
        $session->filter = $filter;

        return new TemplateViewModel('Software\Manage.latte', [
            'software' => $software,
            'order' => $order['order'],
            'direction' => $order['direction'],
            'filter' => $filter,
            'csrfToken' => CsrfValidator::getToken(),
        ]);
    }

    /**
     * Confirm software definition actions
     */
    public function confirmAction()
    {
        $session = new Container('ManageSoftware');
        $formData = $this->params()->fromPost();
        if ($this->softwareManagementForm->getValidationMessages($formData)) {
            return $this->redirectToRoute('software', 'index', ['filter' => $session['filter']]);
        } else {
            $session->setExpirationHops(1);
            $session['software'] = $formData['software'];
            $session['display'] = isset($formData['accept']);

            return new TemplateViewModel('Software/Confirm.latte', $session->getArrayCopy());
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
        $session = new Container('ManageSoftware');

        if (isset($post['no'])) {
            return $this->redirectToRoute('software', 'index', ['filter' => $session['filter']]);
        } elseif (isset($post['yes'])) {
            foreach ($session['software'] as $name) {
                $this->_softwareManager->setDisplay($name, $session['display']);
            }
            return $this->redirectToRoute('software', 'index', ['filter' => $session['filter']]);
        } else {
            $response = $this->getResponse();
            $response->setStatusCode(400);
            return $response;
        }
    }
}
