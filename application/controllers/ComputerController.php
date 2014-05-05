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

class ComputerController extends Zend_Controller_Action
{

    public function preDispatch()
    {
        // Fetch computer with given ID for actions referring to a particular computer
        switch ($this->_getParam('action')) {
            case 'index':
            case 'search':
            case 'import':
                return; // no specific computer for these actions
        }

        $computer = clone \Library\Application::getService('Model\Computer\Computer');
        try {
            $computer->fetchById($this->_getParam('id'));
        } catch(\RuntimeException $e) {
            $this->_helper->redirector('index', 'computer');
        }
        $this->computer = $computer;
        $this->view->computer = $computer;
        Zend_Registry::set('subNavigation', 'Inventory');
    }

    public function indexAction()
    {
        if ($this->_getParam('customSearch')) {
            // Submitted from search form
            $form = \Library\Application::getService('FormElementManager')->get('Console\Form\Search');
            $form->setData($this->_getAllParams());
            if ($form->isValid()) {
                $isCustomSearch = true;

                $data = $form->getData();
                $filter = $data['filter'];
                $search = $data['search'];
                $operator = $data['operator'];
                $invert = $data['invert'];

                // Request minimal column list and add columns for non-equality searches
                $columns = array('Name', 'UserName', 'InventoryDate');
                if ($data['operator'] != 'eq' and !in_array($filter, $columns)) {
                    $columns[] = $filter;
                }
            } else {
                $this->_helper->redirector(
                    'search',
                    'computer',
                    null,
                    $this->_getAllParams()
                );
                return;
            }
        } else {
            // Direct query via URL
            $isCustomSearch = false;

            $filter = $this->_getParam('filter');
            $search = $this->_getParam('search');
            $invert = $this->_getParam('invert');
            $operator = $this->_getParam('operator');

            if (!$filter) {
                $index = 1;
                while ($this->_getParam('filter' . $index)) {
                    $filter[] = $this->_getParam('filter' . $index);
                    $search[] = $this->_getParam('search' . $index);
                    $invert[] = $this->_getParam('invert' . $index);
                    $operator[] = $this->_getParam('operator' . $index);
                    $index++;
                }
            }

            $columns = explode(
                ',',
                $this->_getParam(
                    'columns',
                    'Name,UserName,OsName,Type,CpuClock,PhysicalMemory,InventoryDate'
                )
            );
        }

        $this->_helper->ordering('InventoryDate', 'desc');
        $this->view->computers = Model_Computer::createStatementStatic(
            $columns,
            $this->view->order,
            $this->view->direction,
            $filter,
            $search,
            $invert,
            $operator
        );

        $jumpto = $this->_getParam('jumpto');
        if (!method_exists($this, $jumpto . 'Action')) {
            $jumpto = 'general'; // Default for missing or invalid argument
        }
        $this->view->jumpto = $jumpto;

        $this->view->filter = $filter;
        $this->view->search = $search;
        $this->view->invert = $invert;
        $this->view->operator = $operator;
        $this->view->isCustomSearch = $isCustomSearch;
        $this->view->columns = $columns;
    }

    public function generalAction()
    {
    }

    public function windowsAction()
    {
        $windows = $this->computer->getWindows();

        if (Model_Database::supportsManualProductKey()) {
            $form = new Form_ProductKey;

            if ($this->getRequest()->isPost() and $form->isValid($_POST)) {
                $windows->setManualProductKey($form->key->getValue());
            }

            // Always retrieve key, even if it has just been set, because the
            // model may have altered it.
            $form->key->setValue($windows->getManualProductKey());
            $this->view->form = $form;
        }

        $this->view->windows = $windows;
    }

    public function networkAction()
    {
    }

    public function storageAction()
    {
    }

    public function displayAction()
    {
    }

    public function biosAction()
    {
    }

    public function systemAction()
    {
    }

    public function printersAction()
    {
    }

    public function softwareAction()
    {
        $this->_helper->ordering('Name');
    }

    public function msofficeAction()
    {
        $this->_helper->ordering('Name');
    }

    public function registryAction()
    {
        $this->_helper->ordering('Value.Name');
    }

    public function vmsAction()
    {
        $this->_helper->ordering('Name');
    }

    public function miscAction()
    {
    }

    public function userdefinedAction()
    {
        $form = new Form_UserDefinedInfo;

        if ($this->getRequest()->isPost()) {
            if ($form->isValid($_POST)) {
                $this->computer->setUserDefinedInfo($form->getValues());

                $session = new Zend_Session_Namespace('UpdateUserdefinedInfo');
                $session->setExpirationHops(1);
                $session->success = true;

                $this->_helper->redirector(
                    'userdefined',
                    'computer',
                    null,
                    array('id' => $this->computer->getId())
                );
                return;
            }
        } else {
            foreach ($this->computer->getUserDefinedInfo() as $name => $value) {
                $form->setDefault($name, $value);
            }
        }
        $this->view->form = $form;
    }

    public function packagesAction()
    {
        $this->_helper->ordering('Name');
    }

    public function groupsAction()
    {
        $this->_helper->ordering('GroupName');
    }

    public function configurationAction()
    {
        $form = new Form_Configuration(array('object' => $this->computer));
        if ($this->getRequest()->isPost()) {
            if ($form->isValid($_POST)) {
                $form->process();
                $this->_helper->redirector(
                    'configuration',
                    'computer',
                    null,
                    array('id' => $this->computer->getId())
                );
            }
        }
        $this->view->form = $form;
    }

    public function deleteAction()
    {
        $form = new Form_YesNo_DeleteComputer;

        if ($this->getRequest()->isPost()) {
            if ($form->isValid($_POST)) {
                if ($this->_getParam('yes')) {
                    $session = new Zend_Session_Namespace('ComputerMessages');
                    $session->setExpirationHops(1);
                    $session->computerName = $this->computer->getName();

                    if (
                        $this->computer->delete(
                            false,
                            $form->getValue('DeleteInterfaces')
                        )
                    ) {
                        $session->success = true;
                        $session->message = $this->view->translate(
                            'Computer \'%s\' was successfully deleted.'
                        );
                    } else {
                        $session->success = false;
                        $session->message = $this->view->translate(
                            'Computer \'%s\' could not be deleted.'
                        );
                    }
                    $this->_helper->redirector('index', 'computer');
                } else {
                    $this->_helper->redirector(
                        'general',
                        'computer',
                        null,
                        array('id' => $this->computer->getId())
                    );
                }
            }
        } else {
            $this->view->form = $form;
        }
    }

    public function removepackageAction()
    {
        $session = new Zend_Session_Namespace('RemovePackage');

        if ($this->getRequest()->isGet()) {
            $session->setExpirationHops(1);
            $session->packageName = $this->_getParam('name');
            $session->computerId = $this->_getParam('id');
            return; // proceed with view script
        }

        $id = $session->computerId;
        if ($this->_getParam('yes')) {
            $this->computer->unaffectPackage($session->packageName);
        }

        $this->_helper->redirector(
            'packages',
            'computer',
            null,
            array('id' => $id)
        );
    }

    public function installpackageAction()
    {
        $computer = $this->computer;
        $form = new Form_AffectPackages;
        $form->addPackages($computer);
        if ($form->isValid($_POST)) {
            $packages = array_keys($form->getValues());
            foreach ($packages as $packageName) {
                $computer->installPackage($packageName);
            }
        }
        $this->_helper->redirector(
            'packages',
            'computer',
            null,
            array('id' => $computer->getId())
        );
    }

    public function managegroupsAction()
    {
        $computer = $this->computer;

        $form = new Form_ManageGroupMemberships;
        $form->addGroups($computer);
        if ($form->isValid($_POST)) {
            $computer->setGroups($form->getValues());
        }
        $this->_helper->redirector(
            'groups',
            'computer',
            null,
            array('id' => $computer->getId())
        );
    }

    public function searchAction()
    {
        $form = \Library\Application::getService('FormElementManager')->get('Console\Form\Search');
        $data = $this->_getAllParams();
        if (isset($data['filter'])) {
            $form->setData($data);
            $form->isValid(); // Set validation messages
        }
        $form->setAttribute('method', 'GET');
        $form->setAttribute('action', $this->_helper->url('index'));
        $this->view->form = $form;
    }

    public function importAction()
    {
        $form = new Form_Import;
        if ($this->getRequest()->isPost() and $form->isValid($_POST)) {
            // Read content of uploaded file
            $file = $form->getElement('File');
            $file->receive();
            $file = $file->getFileName();
            $data = file_get_contents($file);
            if ($data === false) {
                throw new RuntimeException('Could not read uploaded file ' . $file);
            }

            // Post content to communication server
            $request = new \Zend\Http\Client(
                \Library\Application::getService('Model\Config')->communicationServerUri,
                array(
                    'strictredirects' => 'true', // required for POST requests
                    'useragent' => 'Braintacle_local_upload', // Substring 'local' required for correct server operation
                )
            );
            $request->setMethod('POST')
                    ->setHeaders(array('Content-Type' => 'application/x-compress'))
                    ->setRawBody($data);
            $response = $request->send();

            if ($response->isSuccess()) {
                $this->_helper->redirector('index', 'computer');
            } else {
                $this->view->response = $response; // View script can display message
            }
        }

        // Display form
        $this->view->form = $form;
    }

    public function exportAction()
    {
        // Get XML document
        $document = $this->computer->toDomDocument();
        if (APPLICATION_ENV == 'development') {
            $document->forceValid();
        }
        $filename = $document->getFilename(); // Preserve before next step
        $document = $document->saveXml();

        // Set up response for downloadable document
        $response = $this->getResponse();
        $response->setHeader('Content-Type', 'text/xml; charset="utf-8"');
        $response->setHeader(
            'Content-Disposition',
            'attachment; filename="' . $filename . '"'
        );
        $response->setHeader('Content-Length', strlen($document));
        $response->setBody($document);

        // End here, no view script invocation
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
    }
}
