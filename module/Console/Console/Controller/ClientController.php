<?php
/**
 * Controller for all client-related actions.
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

namespace Console\Controller;

/**
 * Controller for all client-related actions.
 */
class ClientController extends \Zend\Mvc\Controller\AbstractActionController
{
    /**
     * Computer prototype
     * @var \Model_Computer
     */
    protected $_computer;

    /**
     * Group manager
     * @var \Model\Group\GroupManager
     */
    protected $_groupManager;

    /**
     * Form manager
     * @var \Zend\Form\FormElementManager
     */
    protected $_formManager;

    /**
     * Application config
     * @var \Model\Config
     */
    protected $_config;

    /**
     * Inventory uploader
     * @var \Library\InventoryUploader
     */
    protected $_inventoryUploader;

    /**
     * Client selected for client-specific actions
     * @var \Model_Computer
     */
    protected $_currentClient;

    /**
     * Constructor
     *
     * @param \Model_Computer $computer
     * @param \Model\Group\GroupManager $groupManager
     * @param \Zend\Form\FormElementManager $formManager
     * @param \Model\Config $config
     * @param \Library\InventoryUploader $inventoryUploader
     */
    public function __construct(
        \Model_Computer $computer,
        \Model\Group\GroupManager $groupManager,
        \Zend\Form\FormElementManager $formManager,
        \Model\Config $config,
        \Library\InventoryUploader $inventoryUploader
    )
    {
        $this->_computer = $computer;
        $this->_groupManager = $groupManager;
        $this->_formManager = $formManager;
        $this->_config = $config;
        $this->_inventoryUploader = $inventoryUploader;
    }

    /** {@inheritdoc} */
    public function dispatch(
        \Zend\Stdlib\RequestInterface $request,
        \Zend\Stdlib\ResponseInterface $response = null
    )
    {
        // Fetch client with given ID for actions referring to a particular client
        $action = $this->getEvent()->getRouteMatch()->getParam('action');
        if ($action != 'index' and $action != 'search' and $action != 'import') {
            $this->_currentClient = clone $this->_computer;
            try {
                $this->_currentClient->fetchById(
                    $request->getQuery('id')
                );
            } catch(\RuntimeException $e) {
                // Client does not exist - may happen when URL has become stale.
                $this->flashMessenger()->addErrorMessage('The requested client does not exist.');
                return $this->redirectToRoute('client', 'index');
            }
        }
        return parent::dispatch($request, $response);
    }

    /**
     * Show list of clients, filtered by various criteria
     *
     * All query parameters are optional, but the filter, search, operator and
     * invert parameters should match.
     *
     * - filter or filter1, filter2...: (string|array) Name of a filter to apply
     * - search or search1, search2...: (string|array) Filter criteria
     * - operator or operator1, operator2...: (string|array) Operator for filter
     * - invert or invert1, invert2...: (bool|array) Invert filter results
     * - columns: Comma-separated list of columns to display (a default set is available)
     * - jumpto: Subpage (action) for the client link (default: general)
     *
     * This action also acts as a handler for the search form (via GET method),
     * denoted by the presence of the customSearch parameter.
     *
     * @return array|\Zend\Http\Response array(filter, search, operator, invert,
     * columns[], jumpto, isCustomSearch, order, direction) or redirect response
     * in case of invalid search form data
     */
    public function indexAction()
    {
        $params = $this->params();
        if ($params->fromQuery('customSearch')) {
            // Submitted from search form
            $form = $this->_formManager->get('Console\Form\Search');
            $form->remove('_csrf');
            $form->setData($params->fromQuery());
            if ($form->isValid()) {
                $isCustomSearch = true;

                $data = $form->getData();
                $filter = $data['filter'];
                $search = $data['search'];
                $operator = $data['operator'];
                $invert = $data['invert'];

                // Request minimal column list and add columns for non-equality searches
                $columns = array('Name', 'UserName', 'InventoryDate');
                if (($invert or $data['operator'] != 'eq') and !in_array($filter, $columns)) {
                    $columns[] = $filter;
                }
            } else {
                return $this->redirectToRoute('client', 'search', $params->fromQuery());
            }
        } else {
            // Direct query via URL with optional builtin filter
            $isCustomSearch = false;

            $filter = $params->fromQuery('filter');
            $search = $params->fromQuery('search');
            $invert = $params->fromQuery('invert');
            $operator = $params->fromQuery('operator');

            if (!$filter) {
                $index = 1;
                while ($params->fromQuery('filter' . $index)) {
                    $filter[] = $params->fromQuery('filter' . $index);
                    $search[] = $params->fromQuery('search' . $index);
                    $operator[] = $params->fromQuery('operator' . $index);
                    $invert[] = $params->fromQuery('invert' . $index);
                    $index++;
                }
            }

            $columns = explode(
                ',',
                $params->fromQuery(
                    'columns',
                    'Name,UserName,OsName,Type,CpuClock,PhysicalMemory,InventoryDate'
                )
            );
        }

        $vars = $this->getOrder('InventoryDate', 'desc');
        $vars['clients'] = $this->_computer->fetch(
            $columns,
            $vars['order'],
            $vars['direction'],
            $filter,
            $search,
            $operator,
            $invert
        );

        $jumpto = $params->fromQuery('jumpto');
        if (!method_exists($this, static::getMethodFromAction($jumpto))) {
            $jumpto = 'general'; // Default for missing or invalid argument
        }
        $vars['jumpto'] = $jumpto;

        $vars['filter'] = $filter;
        $vars['search'] = $search;
        $vars['operator'] = $operator;
        $vars['invert'] = $invert;
        $vars['isCustomSearch'] = $isCustomSearch;
        $vars['columns'] = $columns;

        return $vars;
    }

    /**
     * General information about a client
     *
     * @return array client
     */
    public function generalAction()
    {
        return array('client' => $this->_currentClient);
    }

    /**
     * Information about a client's Windows installation
     *
     * @return array client, windows, form (Product key form)
     */
    public function windowsAction()
    {
        $windows = $this->_currentClient['Windows'];
        $form = $this->_formManager->get('Console\Form\ProductKey');

        if ($this->getRequest()->isPost()) {
            $form->setData($this->params()->fromPost());
            if ($form->isValid()) {
                $data = $form->getData();
                $windows['ManualProductKey'] = $data['Key'];
                return $this->redirectToRoute(
                    'client',
                    'windows',
                    array('id' => $this->_currentClient['Id'])
                );
            }
        } else {
            $form->setData(array('Key' => $windows['ManualProductKey']));
        }

        return array(
            'client' => $this->_currentClient,
            'windows' => $windows,
            'form' => $form,
        );
    }

    /**
     * Information about a client's network settings, interfaces and devices
     *
     * @return array client
     */
    public function networkAction()
    {
        return array('client' => $this->_currentClient);
    }

    /**
     * Information about a client's storage devices and filesystems
     *
     * @return array client
     */
    public function storageAction()
    {
        return array('client' => $this->_currentClient);
    }

    /**
     * Information about a client's display controllers and devices
     *
     * @return array client
     */
    public function displayAction()
    {
        return array('client' => $this->_currentClient);
    }

    /**
     * Information about a client's BIOS/UEFI
     *
     * @return array client
     */
    public function biosAction()
    {
        return array('client' => $this->_currentClient);
    }

    /**
     * Information about a client's RAM, controllers and extension slots
     *
     * @return array client
     */
    public function systemAction()
    {
        return array('client' => $this->_currentClient);
    }

    /**
     * Information about a client's printers
     *
     * @return array client
     */
    public function printersAction()
    {
        return array('client' => $this->_currentClient);
    }

    /**
     * Information about a client's software
     *
     * @return array client, order, direction, displayBlacklistedSoftware
     */
    public function softwareAction()
    {
        $vars = $this->getOrder('Name');
        $vars['client'] = $this->_currentClient;
        $vars['displayBlacklistedSoftware'] = $this->_config->displayBlacklistedSoftware;
        return $vars;
    }

    /**
     * Information about a client's MS Office products (Windows only)
     *
     * @return array client, order, direction
     */
    public function msofficeAction()
    {
        return $this->getOrder('Name') + array('client' => $this->_currentClient);
    }

    /**
     * Information about a client's registry values (Windows only)
     *
     * @return array client, order, direction
     */
    public function registryAction()
    {
        return $this->getOrder('Value.Name') + array('client' => $this->_currentClient);
    }

    /**
     * Information about virtual machines hosted on a client
     *
     * @return array client, order, direction
     */
    public function virtualmachinesAction()
    {
        return $this->getOrder('Name') + array('client' => $this->_currentClient);
    }

    /**
     * Information about a client's audio devices, input devices and ports
     *
     * @return array client, order, direction
     */
    public function miscAction()
    {
        return array('client' => $this->_currentClient);
    }

    /**
     * Display/edit custom fields
     *
     * @return array|\Zend\Http\Response [client, form (Console\Form\CustomFields) or redirect response]
     */
    public function customfieldsAction()
    {
        $form = $this->_formManager->get('Console\Form\CustomFields');

        if ($this->getRequest()->isPost()) {
            $form->setData($this->params()->fromPost());
            if ($form->isValid()) {
                $data = $form->getData();
                $this->_currentClient->setUserDefinedInfo($data['Fields']);
                $this->flashMessenger()->addSuccessMessage('The information was successfully updated.');
                return $this->redirectToRoute(
                    'client',
                    'customfields',
                    array('id' => $this->_currentClient['Id'])
                );
            }
        } else {
            $form->setData(array('Fields' => $this->_currentClient['CustomFields']->getArrayCopy()));
        }
        return array(
            'client' => $this->_currentClient,
            'form' => $form
        );
    }

    /**
     * Status and management of assigned packages
     *
     * @return array client, order, direction [, form (Console\Form\Package\Assign) if packages are available]
     */
    public function packagesAction()
    {
        $vars = $this->getOrder('Name');
        $vars['client'] = $this->_currentClient;
        // Add package installation form if packages are available
        $packages = $this->_currentClient->getInstallablePackages();
        if ($packages) {
            $form = $this->_formManager->get('Console\Form\Package\Assign');
            $form->setPackages($packages);
            $form->setAttribute(
                'action',
                $this->urlFromRoute(
                    'client',
                    'installpackage',
                    array('id' => $this->_currentClient['Id'])
                )
            );
            $vars['form'] = $form;
        }
        return $vars;
    }

    /**
     * Display and manage group memberships
     *
     * @return array client, order, direction [, form (Console\Form\) if groups are available]
     */
    public function groupsAction()
    {
        $vars = $this->getOrder('GroupName');
        $vars['client'] = $this->_currentClient;
        $vars['memberships'] = $this->_currentClient->getGroups(\Model_GroupMembership::TYPE_ALL);

        $groups = $this->_groupManager->getGroups(null, null, 'Name');
        if ($groups->count()) {
            $data = array();
            // Create form data for all groups with default value
            foreach ($groups as $group) {
                $data['Groups'][$group['Name']] = \Model_GroupMembership::TYPE_DYNAMIC;
            }
            // Overlay with actual memberships
            foreach ($vars['memberships'] as $membership) {
                $data['Groups'][$membership['GroupName']] = $membership['Membership'];
            }
            $form = $this->_formManager->get('Console\Form\GroupMemberships');
            $form->setData($data);
            $form->setAttribute(
                'action',
                $this->urlFromRoute(
                    'client',
                    'managegroups',
                    array('id' => $this->_currentClient['Id'])
                )
            );
            $vars['form'] = $form;
        }
        return $vars;
    }

    /**
     * Display/edit client configuration
     *
     * @return array|\Zend\Http\Response [client, form (Console\Form\ClientConfig)] or redirect response
     */
    public function configurationAction()
    {
        $form = $this->_formManager->get('Console\Form\ClientConfig');
        $form->setClientObject($this->_currentClient);
        if ($this->getRequest()->isPost()) {
            $form->setData($this->params()->fromPost());
            if ($form->isValid()) {
                $form->process();
                return $this->redirectToRoute(
                    'client',
                    'configuration',
                    array('id' => $this->_currentClient['Id'])
                );
            }
        } else {
            $form->setData($this->_currentClient->getAllConfig());
        }
        return array(
            'client' => $this->_currentClient,
            'form' => $form
        );
    }

    /**
     * Delete client, display confirmation form
     *
     * @return array|\Zend\Http\Response [client, form (Console\Form\DeleteClient)] or redirect response
     */
    public function deleteAction()
    {
        $form = $this->_formManager->get('Console\Form\DeleteClient');
        if ($this->getRequest()->isPost()) {
            if ($this->params()->fromPost('yes')) {
                $name = $this->_currentClient['Name'];
                if (
                    $this->_currentClient->delete(
                        false,
                        (bool) $this->params()->fromPost('DeleteInterfaces')
                    )
                ) {
                    $this->flashMessenger()->addSuccessMessage(
                        array($this->_("Client '%s' was successfully deleted.") => $name)
                    );
                } else {
                    $this->flashMessenger()->addErrorMessage(
                        array($this->_("Client '%s' could not be deleted.") => $name)
                    );
                }
                return $this->redirectToRoute('client', 'index');
            } else {
                return $this->redirectToRoute(
                    'client',
                    'general',
                    array('id' => $this->_currentClient['Id'])
                );
            }
        } else {
            return array(
                'client' => $this->_currentClient,
                'form' => $form
            );
        }
    }

    /**
     * Remove package assignment, display confirmation form
     *
     * @return array|\Zend\Http\Response array(packageName) or redirect response
     */
    public function removepackageAction()
    {
        $params = $this->params();
        if ($this->getRequest()->isPost()) {
            if ($params->fromPost('yes')) {
                $this->_currentClient->unaffectPackage($params->fromQuery('package'));
            }
            return $this->redirectToRoute(
                'client',
                'packages',
                array('id' => $this->_currentClient['Id'])
            );
        } else {
            return array('packageName' => $params->fromQuery('package'));
        }
    }

    /**
     * Install packages from Console\Form\Package\Assign (POST only)
     *
     * @return \Zend\Http\Response redirect response
     */
    public function installpackageAction()
    {
        if ($this->getRequest()->isPost()) {
            $form = $this->_formManager->get('Console\Form\Package\Assign');
            $form->setData($this->params()->fromPost());
            if ($form->isValid()) {
                $data = $form->getData();
                foreach ($data['Packages'] as $name => $install) {
                    if ($install) {
                        $this->_currentClient->installPackage($name);
                    }
                }
            }
        }
        return $this->redirectToRoute(
            'client',
            'packages',
            array('id' => $this->_currentClient['Id'])
        );
    }

    /**
     * Set group memberships from Console\Form\GroupMemberships (POST only)
     *
     * @return \Zend\Http\Response redirect response
     */
    public function managegroupsAction()
    {
        if ($this->getRequest()->isPost()) {
            $form = $this->_formManager->get('Console\Form\GroupMemberships');
            $form->setData($this->params()->fromPost());
            if ($form->isValid()) {
                $data = $form->getData();
                $this->_currentClient->setGroupsByName($data['Groups']);
            }
        }
        return $this->redirectToRoute(
            'client',
            'groups',
            array('id' => $this->_currentClient['Id'])
        );
    }

    /**
     * Show search form (handled by index action)
     *
     * Params (optional): filter, search, operator, invert
     *
     * @return \Zend\View\Model\ViewModel Form template
     */
    public function searchAction()
    {
        $form = $this->_formManager->get('Console\Form\Search');
        $form->remove('_csrf');
        $data = $this->params()->fromQuery();
        if (isset($data['filter'])) {
            $form->setData($data);
            $form->isValid(); // Set validation messages
        }
        $form->setAttribute('method', 'GET');
        $form->setAttribute('action', $this->urlFromRoute('client', 'index'));
        return $this->printForm($form);
    }

    /**
     * Import client via file upload
     *
     * @return array|\Zend\Http\Response array(form [, uri, response]) or redirect response
     */
    public function importAction()
    {
        $form = $this->_formManager->get('Console\Form\Import');
        $vars = array('form' => $form);
        if ($this->getRequest()->isPost()) {
            $form->setData($this->params()->fromFiles() + $this->params()->fromPost());
            if ($form->isValid()) {
                $data = $form->getData();
                $response = $this->_inventoryUploader->uploadFile($data['File']['tmp_name']);
                if ($response->isSuccess()) {
                    return $this->redirectToRoute('client', 'index');
                } else {
                    $vars['response'] = $response;
                    $vars['uri'] = $this->_config->communicationServerUri;
                }
            }
        }
        return $vars;
    }

    /**
     * Download client as XML file
     *
     * @return \Zend\Http\Response Response with downloadable XML content
     */
    public function exportAction()
    {
        $document = $this->_currentClient->toDomDocument();
        if (\Library\Application::isDevelopment()) {
            $document->forceValid();
        }
        $filename = $document->getFilename();
        $xml = $document->saveXml();
        $response = $this->getResponse();
        $response->getHeaders()->addHeaders(
            array(
                'Content-Type' => 'text/xml; charset="utf-8"',
                'Content-Disposition' => "attachment; filename=\"$filename\"",
                'Content-Length' => strlen($xml),
            )
        );
        $response->setContent($xml);
        return $response;
    }
}
