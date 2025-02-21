<?php

/**
 * Controller for managing groups
 *
 * Copyright (C) 2011-2025 Holger Schletz <holger.schletz@web.de>
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

use Laminas\Stdlib\RequestInterface;
use Laminas\Stdlib\ResponseInterface;

/**
 * Controller for managing groups
 */
class GroupController extends \Laminas\Mvc\Controller\AbstractActionController
{
    /**
     * Group manager
     * @var \Model\Group\GroupManager
     */
    protected $_groupManager;

    /**
     * Client configuration form
     * @var \Console\Form\ClientConfig
     */
    protected $_clientConfigForm;

    /**
     * Current group - populated automatically for appropriate actions
     * @var \Model\Group\Group
     */
    protected $_currentGroup;

    public function __construct(
        \Model\Group\GroupManager $groupManager,
        \Console\Form\ClientConfig $clientConfigForm
    ) {
        $this->_groupManager = $groupManager;
        $this->_clientConfigForm = $clientConfigForm;
    }

    /** {@inheritdoc} */
    public function dispatch(RequestInterface $request, ?ResponseInterface $response = null)
    {
        $event = $this->getEvent();

        // Fetch group with given name for actions referring to a particular group
        $action = $event->getRouteMatch()->getParam('action');
        if ($action != 'index' and $action != 'add') {
            try {
                $this->_currentGroup = $this->_groupManager->getGroup(
                    $request->getQuery('name')
                );
            } catch (\RuntimeException $e) {
                // Group does not exist - may happen when URL has become stale.
                $this->flashMessenger()->addErrorMessage($this->_('The requested group does not exist.'));
                return $this->redirectToRoute('group', 'index');
            }
        }

        $event->setParam('template', 'MainMenu/GroupMenuLayout.latte');

        return parent::dispatch($request, $response);
    }

    /**
     * Group configuration form
     *
     * @return array|\Laminas\Http\Response array(form, group) or redirect response
     */
    public function configurationAction()
    {
        $this->_clientConfigForm->setClientObject($this->_currentGroup);
        if ($this->getRequest()->isPost()) {
            $this->_clientConfigForm->setData($this->params()->fromPost());
            if ($this->_clientConfigForm->isValid()) {
                $this->_clientConfigForm->process();
                return $this->redirectToRoute(
                    'group',
                    'configuration',
                    array('name' => $this->_currentGroup['Name'])
                );
            }
        } else {
            $this->_clientConfigForm->setData($this->_currentGroup->getAllConfig());
        }
        return array(
            'group' => $this->_currentGroup,
            'form' => $this->_clientConfigForm,
        );
    }
}
