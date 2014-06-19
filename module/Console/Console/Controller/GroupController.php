<?php
/**
 * Controller for managing groups
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
 * Controller for managing groups
 */
class GroupController extends \Zend\Mvc\Controller\AbstractActionController
{
    /**
     * Group prototype
     * @var \Model_Group
     */
    protected $_group;

    /**
     * Computer prototype
     * @var \Model_Computer
     */
    protected $_computer;

    /**
     * Package assignment form
     * @var \Form_AffectPackages
     */
    protected $_packageAssignmentForm;

    /**
     * Add to group form
     * @var \Form_AddToGroup
     */
    protected $_addToGroupForm;

    /**
     * Client configuration form
     * @var \Form_Configuration
     */
    protected $_clientConfigForm;

    /**
     * Current group - populated automatically for appropriate actions
     * @var \Model_Group
     */
    protected $_currentGroup;

    /**
     * Constructor
     *
     * @param \Model_Group $group
     * @param \Model_Computer $computer
     * @param \Form_AffectPackages $packageAssignmentForm
     * @param \Form_AddToGroup $addToGroupForm
     * @param \Form_Configuration $clientConfigForm
     */
    public function __construct(
        \Model_Group $group,
        \Model_Computer $computer,
        \Form_AffectPackages $packageAssignmentForm,
        \Form_AddToGroup $addToGroupForm,
        \Form_Configuration $clientConfigForm
    )
    {
        $this->_group = $group;
        $this->_computer = $computer;
        $this->_packageAssignmentForm = $packageAssignmentForm;
        $this->_addToGroupForm = $addToGroupForm;
        $this->_clientConfigForm = $clientConfigForm;
    }

    /** {@inheritdoc} */
    public function dispatch(
        \Zend\Stdlib\RequestInterface $request,
        \Zend\Stdlib\ResponseInterface $response = null
    )
    {
        // Fetch group with given ID for actions referring to a particular group
        $action = $this->getEvent()->getRouteMatch()->getParam('action');
        if ($action != 'index' and $action != 'add') {
            try {
                $this->_currentGroup = $this->_group->fetchById(
                    $request->getQuery('id')
                );
            } catch(\RuntimeException $e) {
                // Group does not exist - may happen when URL has become stale.
                $this->flashMessenger()->addErrorMessage('The requested group does not exist.');
                return $this->redirectToRoute('group', 'index');
            }
        }
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
            'groups' => $this->_group->fetch(
                array('Name', 'CreationDate', 'Description'),
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
        $this->setActiveMenu('Groups');
        return array('group' => $this->_currentGroup);
    }

    /**
     * Show group members
     *
     * @return array sorting, group, computers, order, direction
     */
    public function membersAction()
    {
        $this->setActiveMenu('Groups');

        $vars['sorting'] = $this->getOrder('InventoryDate', 'desc');
        $vars['group'] = $this->_currentGroup;
        $vars['computers'] = $this->_computer->fetch(
            array('Name', 'UserName', 'InventoryDate', 'Membership'),
            $vars['sorting']['order'],
            $vars['sorting']['direction'],
            'MemberOf',
            $this->_currentGroup
        );
        return $vars;
    }

    /**
     * Show excluded computers
     *
     * @return array sorting, group, computers, order, direction
     */
    public function excludedAction()
    {
        $this->setActiveMenu('Groups');

        $vars['sorting'] = $this->getOrder('InventoryDate', 'desc');
        $vars['group'] = $this->_currentGroup;
        $vars['computers'] = $this->_computer->fetch(
            array('Name', 'UserName', 'InventoryDate'),
            $vars['sorting']['order'],
            $vars['sorting']['direction'],
            'ExcludedFrom',
            $this->_currentGroup
        );
        return $vars;
    }

    /**
     * Show assigned and installable packages
     *
     * @return array sorting, group, packageNames, [form]
     */
    public function packagesAction()
    {
        $this->setActiveMenu('Groups');

        $vars['sorting'] = $this->getOrder('Name');
        $vars['group'] = $this->_currentGroup;
        $vars['packageNames'] = $this->_currentGroup->getPackages($vars['sorting']['direction']);

        // Add package installation form if packages are available.
        if ($this->_packageAssignmentForm->addPackages($this->_currentGroup)) {
            $vars['form'] = $this->_packageAssignmentForm;
        }

        return $vars;
    }

    /**
     * Remove a package
     *
     * POST params: packageName, groupId
     *
     * @return array|\Zend\Http\Response array(packageName, groupId) or redirect response
     */
    public function removepackageAction()
    {
        if ($this->getRequest()->isPost()) {
            if ($this->params()->fromPost('yes')) {
                $this->_currentGroup->unaffectPackage($this->params()->fromQuery('name'));
            }
            return $this->redirectToRoute(
                'group',
                'packages',
                array('id' => $this->params()->fromQuery('id'))
            );
        } else {
            return array('name' => $this->params()->fromQuery('name'));
        }
    }

    /**
     * Assign a package
     *
     * POST only
     *
     * @return \Zend\Http\Response redirect response
     */
    public function installpackageAction()
    {
        $this->_packageAssignmentForm->addPackages($this->_currentGroup);
        if (
            $this->getRequest()->isPost() and
            $this->_packageAssignmentForm->isValid($this->params()->fromPost())
        ) {
            $packages = array_keys($this->_packageAssignmentForm->getValues());
            foreach ($packages as $packageName) {
                $this->_currentGroup->installPackage($packageName);
            }
        }
        return $this->redirectToRoute(
            'group',
            'packages',
            array('id' => $this->params()->fromQuery('id'))
        );
    }

    /**
     * Use Form to set query or include/exclude computers
     *
     * @return array|\Zend\Http\Response array(form) or redirect response
     */
    public function addAction()
    {
        if ($this->getRequest()->isPost() and $this->_addToGroupForm->isValid($this->params()->fromPost())) {
            $group = $this->_addToGroupForm->getGroup();
            $what = $this->_addToGroupForm->getValue('What');
            $members = $this->_computer->fetch(
                array('Id'),
                null,
                null,
                $this->params()->fromQuery('filter'),
                $this->params()->fromQuery('search'),
                $this->params()->fromQuery('operator'),
                $this->params()->fromQuery('invert'),
                false,
                true,
                ($what == \Form_AddToGroup::STORE_FILTER) ? false : true
            );
            switch ($what) {
                case \Form_AddToGroup::STORE_FILTER:
                    $group->setDynamicMembersSql($members);
                    break;
                case \Form_AddToGroup::STORE_RESULT:
                    $group->addComputers($members);
                    break;
                case \Form_AddToGroup::STORE_EXCLUDED:
                    $group->excludeComputers($members);
                    break;
            }
            return $this->redirectToRoute(
                'group',
                'members',
                array('id' => $group['Id'])
            );
        } else {
            return array('form' => $this->_addToGroupForm);
        }
    }

    /**
     * Group configuration form
     *
     * @return array|\Zend\Http\Response array(form, group) or redirect response
     */
    public function configurationAction()
    {
        $this->setActiveMenu('Groups');
        $this->_clientConfigForm->setObject($this->_currentGroup);
        if ($this->getRequest()->isPost() and $this->_clientConfigForm->isValid($this->params()->fromPost())) {
            $this->_clientConfigForm->process();
            return $this->redirectToRoute(
                'group',
                'configuration',
                array('id' => $this->_currentGroup['Id'])
            );
        }
        return array(
            'group' => $this->_currentGroup,
            'form' => $this->_clientConfigForm,
        );
    }

    /**
     * Group deletion form
     *
     * @return array|\Zend\Http\Response array(name) or redirect response
     */
    public function deleteAction()
    {
        $name = $this->_currentGroup['Name'];
        if ($this->getRequest()->isPost()) {
            if ($this->params()->fromPost('yes')) {
                if ($this->_currentGroup->delete()) {
                    $this->flashMessenger()->addSuccessMessage(
                        array('Group \'%s\' was successfully deleted.' => $name)
                    );
                } else {
                    $this->flashMessenger()->addErrorMessage(
                        array('Group \'%s\' could not be deleted.' => $name)
                    );
                }
                return $this->redirectToRoute('group', 'index');
            } else {
                return $this->redirectToRoute(
                    'group',
                    'general',
                    array('id' => $this->_currentGroup['Id'])
                );
            }
        } else {
            return array('name' => $name);
        }
    }
}
