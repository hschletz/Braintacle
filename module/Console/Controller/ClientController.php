<?php

/**
 * Controller for all client-related actions.
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

use Braintacle\Http\RouteHelper;
use Console\View\Helper\Form\Search;

/**
 * Controller for all client-related actions.
 */
class ClientController extends \Laminas\Mvc\Controller\AbstractActionController
{
    /**
     * Client manager
     * @var \Model\Client\ClientManager
     */
    protected $_clientManager;

    /**
     * Registry manager
     * @var \Model\Registry\RegistryManager
     */
    protected $_registryManager;

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
     * Client selected for client-specific actions
     * @var \Model\Client\Client
     */
    protected $_currentClient;

    public function __construct(
        private RouteHelper $routeHelper,
        \Model\Client\ClientManager $clientManager,
        \Model\Registry\RegistryManager $registryManager,
        \Model\SoftwareManager $softwareManager,
        \Laminas\Form\FormElementManager $formManager,
    ) {
        $this->_clientManager = $clientManager;
        $this->_registryManager = $registryManager;
        $this->_softwareManager = $softwareManager;
        $this->_formManager = $formManager;
    }

    /** {@inheritdoc} */
    public function dispatch(
        \Laminas\Stdlib\RequestInterface $request,
        \Laminas\Stdlib\ResponseInterface $response = null
    ) {
        $event = $this->getEvent();

        // Fetch client with given ID for actions referring to a particular client
        $action = $event->getRouteMatch()->getParam('action');
        if ($action != 'index' and $action != 'search' and $action != 'import') {
            try {
                $this->_currentClient = $this->_clientManager->getClient($request->getQuery('id'));
            } catch (\RuntimeException $e) {
                // Client does not exist - may happen when URL has become stale.
                $this->flashMessenger()->addErrorMessage($this->_('The requested client does not exist.'));
                return $this->redirectToRoute('client', 'index');
            }
        }

        $event->setParam('template', 'MainMenu/InventoryMenuLayout.latte');
        $event->setParam('subMenuRoute', 'clientList');

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
     * - columns: Comma-separated list of columns to display (a default set is
     *   available)
     * - jumpto: Subpage (action) for the client link (default: general)
     *
     * This action also acts as a handler for the search form (via GET method),
     * denoted by the presence of the customSearch parameter.
     *
     * @return array|\Laminas\Http\Response array(filter, search, operator,
     * invert, columns[], jumpto, isCustomSearch, order, direction) or redirect
     * response in case of invalid search form data
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
        $vars['clients'] = $this->_clientManager->getClients(
            $columns,
            $vars['order'],
            $vars['direction'],
            $filter,
            $search,
            $operator,
            $invert,
            true,
            $params->fromQuery('distinct') !== null
        );

        $jumpto = $params->fromQuery('jumpto');
        if (!$jumpto || !method_exists($this, static::getMethodFromAction($jumpto))) {
            $jumpto = 'general'; // Default for missing or invalid argument
        }
        $vars['jumpto'] = $jumpto;

        $vars['filter'] = $filter;
        $vars['search'] = $search;
        $vars['operator'] = $operator;
        $vars['invert'] = $invert;
        $vars['isCustomSearch'] = $isCustomSearch;
        $vars['columns'] = $columns;
        $vars['routeHelper'] = $this->routeHelper;

        return $vars;
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
                $this->_softwareManager->setProductKey($this->_currentClient, $data['Key']);
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
     * @return array client, values, order, direction
     */
    public function registryAction()
    {
        $values = array();
        foreach ($this->_registryManager->getValueDefinitions() as $value) {
            $values[$value['Name']] = $value;
        }
        return $this->getOrder('Value') + array('client' => $this->_currentClient, 'values' => $values);
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
     * @return array|\Laminas\Http\Response [client, form (Console\Form\CustomFields) or redirect response]
     */
    public function customfieldsAction()
    {
        $form = $this->_formManager->get('Console\Form\CustomFields');

        if ($this->getRequest()->isPost()) {
            $form->setData($this->params()->fromPost());
            if ($form->isValid()) {
                $data = $form->getData();
                $this->_currentClient->setCustomFields($data['Fields']);
                $this->flashMessenger()->addSuccessMessage($this->_('The information was successfully updated.'));
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
     * Display/edit client configuration
     *
     * @return array|\Laminas\Http\Response [client, form (Console\Form\ClientConfig)] or redirect response
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
     * @return array|\Laminas\Http\Response [client, form (Console\Form\DeleteClient)] or redirect response
     */
    public function deleteAction()
    {
        $form = $this->_formManager->get('Console\Form\DeleteClient');
        if ($this->getRequest()->isPost()) {
            if ($this->params()->fromPost('yes')) {
                $name = $this->_currentClient['Name'];
                try {
                    $this->_clientManager->deleteClient(
                        $this->_currentClient,
                        (bool) $this->params()->fromPost('DeleteInterfaces')
                    );
                    $this->flashMessenger()->addSuccessMessage(
                        sprintf($this->_("Client '%s' was successfully deleted."), $name)
                    );
                } catch (\RuntimeException $e) {
                    $this->flashMessenger()->addErrorMessage(
                        sprintf($this->_("Client '%s' could not be deleted."), $name)
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
     * Show search form (handled by index action)
     *
     * Params (optional): filter, search, operator, invert
     *
     * @return \Laminas\View\Model\ViewModel Form template
     */
    public function searchAction()
    {
        $this->getEvent()->setParam('template', 'MainMenu/SearchMenuLayout.latte');

        $form = $this->_formManager->get('Console\Form\Search');
        $form->remove('_csrf');
        $data = $this->params()->fromQuery();
        if (isset($data['filter'])) {
            $form->setData($data);
            $form->isValid(); // Set validation messages
        }
        $form->setAttribute('method', 'GET');
        $form->setAttribute('action', $this->urlFromRoute('client', 'index'));

        return $this->printForm($form, Search::class);
    }

    /**
     * Import client via file upload
     *
     * @return array|\Laminas\Http\Response array(form [, uri, response]) or redirect response
     */
    public function importAction()
    {
        $this->getEvent()->setParam('subMenuRoute', 'importPage');
        $form = $this->_formManager->get('Console\Form\Import');
        $vars = array('form' => $form);
        if ($this->getRequest()->isPost()) {
            $form->setData($this->params()->fromFiles() + $this->params()->fromPost());
            if ($form->isValid()) {
                $data = $form->getData();
                try {
                    $this->_clientManager->importFile($data['File']['tmp_name']);
                    return $this->redirectToRoute('client', 'index');
                } catch (\RuntimeException $e) {
                    $vars['error'] = $e->getMessage();
                }
            }
        }
        return $vars;
    }
}
