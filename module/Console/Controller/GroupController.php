<?php

/**
 * Controller for managing groups
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

use Braintacle\Direction;
use Console\Form\Package\AssignPackagesForm;
use Console\Template\TemplateViewModel;
use Console\Validator\CsrfValidator;

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
     * Client manager
     * @var \Model\Client\ClientManager
     */
    protected $_clientManager;

    /**
     * Add to group form
     * @var \Console\Form\AddToGroup
     */
    protected $_addToGroupForm;

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

    private AssignPackagesForm $assignPackagesForm;

    public function __construct(
        \Model\Group\GroupManager $groupManager,
        \Model\Client\ClientManager $clientManager,
        AssignPackagesForm $assignPackagesForm,
        \Console\Form\AddToGroup $addToGroupForm,
        \Console\Form\ClientConfig $clientConfigForm
    ) {
        $this->_groupManager = $groupManager;
        $this->_clientManager = $clientManager;
        $this->_addToGroupForm = $addToGroupForm;
        $this->_clientConfigForm = $clientConfigForm;
        $this->assignPackagesForm = $assignPackagesForm;
    }

    /** {@inheritdoc} */
    public function dispatch(
        \Laminas\Stdlib\RequestInterface $request,
        \Laminas\Stdlib\ResponseInterface $response = null
    ) {
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

        $event->setParam('template', 'GroupMenuLayout.latte');

        return parent::dispatch($request, $response);
    }

    /**
     * Show table with overview of groups
     *
     * @return array Array(groups, sorting)
     */
    public function indexAction()
    {
        $sorting = $this->getOrder('Name', 'asc');
        return array(
            'groups' => $this->_groupManager->getGroups(
                null,
                null,
                $sorting['order'],
                $sorting['direction']
            ),
            'sorting' => $sorting,
        );
    }

    /**
     * Show general information about a group
     *
     * @return array group
     */
    public function generalAction()
    {
        return array('group' => $this->_currentGroup);
    }

    /**
     * Show group members
     *
     * @return array sorting, group, clients, order, direction
     */
    public function membersAction()
    {
        $vars['sorting'] = $this->getOrder('InventoryDate', 'desc');
        $vars['group'] = $this->_currentGroup;
        $vars['clients'] = $this->_clientManager->getClients(
            array('Name', 'UserName', 'InventoryDate', 'Membership'),
            $vars['sorting']['order'],
            $vars['sorting']['direction'],
            'MemberOf',
            $this->_currentGroup
        );
        return $vars;
    }

    /**
     * Show excluded clients
     *
     * @return array sorting, group, clients, order, direction
     */
    public function excludedAction()
    {
        $vars['sorting'] = $this->getOrder('InventoryDate', 'desc');
        $vars['group'] = $this->_currentGroup;
        $vars['clients'] = $this->_clientManager->getClients(
            array('Name', 'UserName', 'InventoryDate'),
            $vars['sorting']['order'],
            $vars['sorting']['direction'],
            'ExcludedFrom',
            $this->_currentGroup
        );
        return $vars;
    }

    /**
     * Use Form to set query or include/exclude clients
     *
     * @return array|\Laminas\Http\Response array(form) or redirect response
     */
    public function addAction()
    {
        if ($this->getRequest()->isPost()) {
            $this->_addToGroupForm->setData($this->params()->fromPost());
            if ($this->_addToGroupForm->isValid()) {
                $group = $this->_addToGroupForm->process(
                    $this->params()->fromQuery('filter'),
                    $this->params()->fromQuery('search'),
                    $this->params()->fromQuery('operator'),
                    $this->params()->fromQuery('invert')
                );
                return $this->redirectToRoute(
                    'group',
                    'members',
                    array('name' => $group['Name'])
                );
            }
        }
        return array('form' => $this->_addToGroupForm);
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

    /**
     * Group deletion form
     *
     * @return array|\Laminas\Http\Response array(name) or redirect response
     */
    public function deleteAction()
    {
        $name = $this->_currentGroup['Name'];
        if ($this->getRequest()->isPost()) {
            if ($this->params()->fromPost('yes')) {
                try {
                    $this->_groupManager->deleteGroup($this->_currentGroup);
                    $this->flashMessenger()->addSuccessMessage(
                        sprintf($this->_("Group '%s' was successfully deleted."), $name)
                    );
                } catch (\Model\Group\RuntimeException $e) {
                    $this->flashMessenger()->addErrorMessage(
                        sprintf($this->_("Group '%s' could not be deleted. Try again later."), $name)
                    );
                }
                return $this->redirectToRoute('group', 'index');
            } else {
                return $this->redirectToRoute(
                    'group',
                    'general',
                    array('name' => $this->_currentGroup['Name'])
                );
            }
        } else {
            return array('name' => $name);
        }
    }
}
