<?php
/**
 * Controller for all computer-related actions.
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
 * Controller for all computer-related actions.
 */
class ComputerController extends \Zend\Mvc\Controller\AbstractActionController
{
    /**
     * Computer prototype
     * @var \Model_Computer
     */
    protected $_computer;

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
     * Computer selected for computer-specific actions
     * @var \Model_Computer
     */
    protected $_currentComputer;

    /**
     * Constructor
     *
     * @param \Model_Computer $computer
     * @param \Zend\Form\FormElementManager $formManager
     * @param \Model\Config $config
     * @param \Library\InventoryUploader $inventoryUploader
     */
    public function __construct(
        \Model_Computer $computer,
        \Zend\Form\FormElementManager $formManager,
        \Model\Config $config,
        \Library\InventoryUploader $inventoryUploader
    )
    {
        $this->_computer = $computer;
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
        // Fetch computer with given ID for actions referring to a particular computer
        $action = $this->getEvent()->getRouteMatch()->getParam('action');
        if ($action != 'index' and $action != 'search' and $action != 'import') {
            $this->_currentComputer = clone $this->_computer;
            try {
                $this->_currentComputer->fetchById(
                    $request->getQuery('id')
                );
            } catch(\RuntimeException $e) {
                // Computer does not exist - may happen when URL has become stale.
                $this->flashMessenger()->addErrorMessage('The requested computer does not exist.');
                return $this->redirectToRoute('computer', 'index');
            }
        }
        return parent::dispatch($request, $response);
    }

    /**
     * Show list of computers, filtered by various criteria
     *
     * All query parameters are optional, but the filter, search, operator and
     * invert parameters should match.
     *
     * - filter or filter1, filter2...: (string|array) Name of a filter to apply
     * - search or search1, search2...: (string|array) Filter criteria
     * - operator or operator1, operator2...: (string|array) Operator for filter
     * - invert or invert1, invert2...: (bool|array) Invert filter results
     * - columns: Comma-separated list of columns to display (a default set is available)
     * - jumpto: Subpage (action) for the computer link (default: general)
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
                return $this->redirectToRoute('computer', 'search', $params->fromQuery());
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
        $vars['computers'] = $this->_computer->fetch(
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
     * General information about a computer
     *
     * @return array computer
     */
    public function generalAction()
    {
        return array('computer' => $this->_currentComputer);
    }

    /**
     * Information about a computer's Windows installation
     *
     * @return array computer, windows, form (Product key form)
     */
    public function windowsAction()
    {
        $windows = $this->_currentComputer['Windows'];
        $form = $this->_formManager->get('Console\Form\ProductKey');

        if ($this->getRequest()->isPost()) {
            $form->setData($this->params()->fromPost());
            if ($form->isValid()) {
                $data = $form->getData();
                $windows['ManualProductKey'] = $data['Key'];
                return $this->redirectToRoute(
                    'computer',
                    'windows',
                    array('id' => $this->_currentComputer['Id'])
                );
            }
        } else {
            $form->setData(array('Key' => $windows['ManualProductKey']));
        }

        return array(
            'computer' => $this->_currentComputer,
            'windows' => $windows,
            'form' => $form,
        );
    }

    /**
     * Information about a computer's network settings, interfaces and devices
     *
     * @return array computer
     */
    public function networkAction()
    {
        return array('computer' => $this->_currentComputer);
    }

    /**
     * Information about a computer's storage devices and filesystems
     *
     * @return array computer
     */
    public function storageAction()
    {
        return array('computer' => $this->_currentComputer);
    }

    /**
     * Information about a computer's display controllers and devices
     *
     * @return array computer
     */
    public function displayAction()
    {
        return array('computer' => $this->_currentComputer);
    }

    /**
     * Information about a computer's BIOS/UEFI
     *
     * @return array computer
     */
    public function biosAction()
    {
        return array('computer' => $this->_currentComputer);
    }

    /**
     * Information about a computer's RAM, controllers and extension slots
     *
     * @return array computer
     */
    public function systemAction()
    {
        return array('computer' => $this->_currentComputer);
    }

    /**
     * Information about a computer's printers
     *
     * @return array computer
     */
    public function printersAction()
    {
        return array('computer' => $this->_currentComputer);
    }

    /**
     * Information about a computer's software
     *
     * @return array computer, order, direction, displayBlacklistedSoftware
     */
    public function softwareAction()
    {
        $vars = $this->getOrder('Name');
        $vars['computer'] = $this->_currentComputer;
        $vars['displayBlacklistedSoftware'] = $this->_config->displayBlacklistedSoftware;
        return $vars;
    }

    /**
     * Information about a computer's MS Office products (Windows only)
     *
     * @return array computer, order, direction
     */
    public function msofficeAction()
    {
        return $this->getOrder('Name') + array('computer' => $this->_currentComputer);
    }

    /**
     * Information about a computer's registry values (Windows only)
     *
     * @return array computer, order, direction
     */
    public function registryAction()
    {
        return $this->getOrder('Value.Name') + array('computer' => $this->_currentComputer);
    }

    /**
     * Information about virtual machines hosted on a computer
     *
     * @return array computer, order, direction
     */
    public function virtualmachinesAction()
    {
        return $this->getOrder('Name') + array('computer' => $this->_currentComputer);
    }

    /**
     * Information about a computer's audio devices, input devices and ports
     *
     * @return array computer, order, direction
     */
    public function miscAction()
    {
        return array('computer' => $this->_currentComputer);
    }

    /**
     * Display/edit custom fields
     *
     * @return array|\Zend\Http\Response [computer, form (Console\Form\CustomFields) or redirect response]
     */
    public function customfieldsAction()
    {
        $form = $this->_formManager->getServiceLocator()->get('Console\Form\CustomFields');

        if ($this->getRequest()->isPost()) {
            if ($form->isValid($this->params()->fromPost())) {
                $this->_currentComputer->setUserDefinedInfo($form->getValues());
                $this->flashMessenger()->addSuccessMessage('The information was successfully updated.');
                return $this->redirectToRoute(
                    'computer',
                    'customfields',
                    array('id' => $this->_currentComputer['Id'])
                );
            }
        } else {
            foreach ($this->_currentComputer['CustomFields'] as $name => $value) {
                $form->setDefault($name, $value);
            }
        }
        return array(
            'computer' => $this->_currentComputer,
            'form' => $form
        );
    }

    /**
     * Status and management of assigned packages
     *
     * @return array computer, order, direction [, form (Console\Form\Package\Assign) if packages are available]
     */
    public function packagesAction()
    {
        $vars = $this->getOrder('Name');
        $vars['computer'] = $this->_currentComputer;
        // Add package installation form if packages are available
        $packages = $this->_currentComputer->getInstallablePackages();
        if ($packages) {
            $form = $this->_formManager->get('Console\Form\Package\Assign');
            $form->setPackages($packages);
            $form->setAttribute(
                'action',
                $this->urlFromRoute(
                    'computer',
                    'installpackage',
                    array('id' => $this->_currentComputer['Id'])
                )
            );
            $vars['form'] = $form;
        }
        return $vars;
    }

    /**
     * Display and manage group memberships
     *
     * @return array computer, order, direction [, form (Console\Form\) if groups are available]
     */
    public function groupsAction()
    {
        $vars = $this->getOrder('GroupName');
        $vars['computer'] = $this->_currentComputer;
        $form = $this->_formManager->getServiceLocator()->get('Console\Form\GroupMemberships');
        if ($form->addGroups($this->_currentComputer)) {
            $form->setAction(
                $this->urlFromRoute(
                    'computer',
                    'managegroups',
                    array('id' => $this->_currentComputer['Id'])
                )
            );
            $vars['form'] = $form;
        }
        return $vars;
    }

    /**
     * Display/edit computer configuration
     *
     * @return array|\Zend\Http\Response [computer, form (Console\Form\ClientConfig)] or redirect response
     */
    public function configurationAction()
    {
        $form = $this->_formManager->getServiceLocator()->get('Console\Form\ClientConfig');
        $form->setObject($this->_currentComputer);
        if ($this->getRequest()->isPost()) {
            if ($form->isValid($this->params()->fromPost())) {
                $form->process();
                return $this->redirectToRoute(
                    'computer',
                    'configuration',
                    array('id' => $this->_currentComputer['Id'])
                );
            }
        }
        return array(
            'computer' => $this->_currentComputer,
            'form' => $form
        );
    }

    /**
     * Delete computer, display confirmation form
     *
     * @return array|\Zend\Http\Response [computer, form (Console\Form\DeleteComputer)] or redirect response
     */
    public function deleteAction()
    {
        $form = $this->_formManager->get('Console\Form\DeleteComputer');
        if ($this->getRequest()->isPost()) {
            if ($this->params()->fromPost('yes')) {
                $name = $this->_currentComputer['Name'];
                if (
                    $this->_currentComputer->delete(
                        false,
                        (bool) $this->params()->fromPost('DeleteInterfaces')
                    )
                ) {
                    $this->flashMessenger()->addSuccessMessage(
                        array("Computer '%s' was successfully deleted." => $name)
                    );
                } else {
                    $this->flashMessenger()->addErrorMessage(
                        array("Computer '%s' could not be deleted." => $name)
                    );
                }
                return $this->redirectToRoute('computer', 'index');
            } else {
                return $this->redirectToRoute(
                    'computer',
                    'general',
                    array('id' => $this->_currentComputer['Id'])
                );
            }
        } else {
            return array(
                'computer' => $this->_currentComputer,
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
                $this->_currentComputer->unaffectPackage($params->fromQuery('package'));
            }
            return $this->redirectToRoute(
                'computer',
                'packages',
                array('id' => $this->_currentComputer['Id'])
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
                        $this->_currentComputer->installPackage($name);
                    }
                }
            }
        }
        return $this->redirectToRoute(
            'computer',
            'packages',
            array('id' => $this->_currentComputer['Id'])
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
            $form = $this->_formManager->getServiceLocator()->get('Console\Form\GroupMemberships');
            $form->addGroups($this->_currentComputer);
            if ($form->isValid($this->params()->fromPost())) {
                $this->_currentComputer->setGroups($form->getValues());
            }
        }
        return $this->redirectToRoute(
            'computer',
            'groups',
            array('id' => $this->_currentComputer['Id'])
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
        $form->setAttribute('action', $this->urlFromRoute('computer', 'index'));
        return $this->printForm($form);
    }

    /**
     * Import computer via file upload
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
                    return $this->redirectToRoute('computer', 'index');
                } else {
                    $vars['response'] = $response;
                    $vars['uri'] = $this->_config->communicationServerUri;
                }
            }
        }
        return $vars;
    }

    /**
     * Download computer as XML file
     *
     * @return \Zend\Http\Response Response with downloadable XML content
     */
    public function exportAction()
    {
        $document = $this->_currentComputer->toDomDocument();
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
