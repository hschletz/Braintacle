<?php
/**
 * Controller for all computer-related actions.
 *
 * $Id$
 *
 * Copyright (C) 2011 Holger Schletz <holger.schletz@web.de>
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

        $computer = Model_Computer::fetchById($this->_getParam('id'));
        if ($computer) {
            $this->computer = $computer;
            $this->view->computer = $computer;
            Zend_Registry::set('subNavigation', 'Inventory');
        } else {
            $this->_redirect('computer');
        }
    }

    public function indexAction()
    {
        $this->_helper->ordering('InventoryDate', 'desc');

        $filter = $this->_getParam('filter');
        $search = $this->_getParam('search');
        $exact = $this->_getParam('exact');
        $invert = $this->_getParam('invert');
        $operator = $this->_getParam('operator');

        if (!$filter) {
            $index = 1;
            while ($this->_getParam('filter' . $index)) {
                $filter[] = $this->_getParam('filter' . $index);
                $search[] = $this->_getParam('search' . $index);
                $exact[] = $this->_getParam('exact' . $index);
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

        $this->view->columns = $columns;

        $this->view->computers = Model_Computer::createStatementStatic(
            $columns,
            $this->view->order,
            $this->view->direction,
            $filter,
            $search,
            $exact,
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
        $this->view->exact = $exact;
        $this->view->invert = $invert;
        $this->view->operator = $operator;
        if ($this->_getParam('customFilter')) {
            $this->view->filterUriPart = $this->getFilterUriPart();
        }
    }

    public function generalAction()
    {
    }

    public function windowsAction()
    {
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

    public function registryAction()
    {
        $this->_helper->ordering('Name');
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

                $this->_redirect('computer/userdefined/id/' . $this->computer->getId());
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
                            null,
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
                    $this->_redirect('computer');
                } else {
                    $this->_redirect('computer/general/id/' . $this->computer->getId());
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

        $this->_redirect('computer/packages/id/' . $id);
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
        $this->_redirect('computer/packages/id/' . $computer->getId());
    }

    public function managegroupsAction()
    {
        $computer = $this->computer;

        $form = new Form_ManageGroupMemberships;
        $form->addGroups($computer);
        if ($form->isValid($_POST)) {
            $computer->setGroups($form->getValues());
        }
        $this->_redirect('computer/groups/id/' . $computer->getId());
    }

    public function searchAction()
    {
        $form = new Form_Search;

        if ($this->getRequest()->isPost()) {
            if ($form->isValid($_POST)) {
                // Request minimal column list and add columns for pattern or inverted searches
                $columns = array('Name', 'UserName', 'InventoryDate');
                if ($form->getValue('invert') or !$form->getValue('exact')) {
                    if (!in_array($form->getValue('filter'), $columns)) {
                        $columns[] = $form->getValue('filter');
                    }
                }

                // Normalize search argument
                $search = $form->getValue('search'); // Will process integers, floats and dates
                if ($form->getType('search') == 'date') {
                    // Convert Zend_Date to short date string
                    $search = $search->get('yyyy-MM-dd');
                }
                $this->_setParam('search', $search);

                // Redirect to index page with all search parameters
                $this->_redirect(
                    'computer/index' . $this->getFilterUriPart() . '/customFilter/1/columns/' . implode(',', $columns)
                );
                return;
            }
        }

        $form->setDefaults($this->_getAllParams());
        // Set form action explicitly to prevent GET parameters leaking into submitted form data
        $form->setAction($this->_helper->url('search'));
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
            $host = Model_Config::get('CommunicationServerAddress');
            $port = Model_Config::get('CommunicationServerPort');
            $request = new Zend_Http_Client(
                "http://$host:$port/ocsinventory",
                array(
                    'strictredirects' => 'true', // required for POST requests
                    'useragent' => 'Braintacle_local_upload', // Substring 'local' required for correct server operation
                )
            );
            $request->setRawData($data, 'application/x-compress');
            $response = $request->request('POST');

            if ($response->isSuccessful()) {
                $this->_redirect('computer');
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

    /**
     * Return the part of the URI that defines the current filter
     * @return string URI part, beginning with '/', or empty string if no filter is active.
     */
    public function getFilterUriPart()
    {
        if (!$this->_getParam('filter')) {
            return '';
        }
        $part = '/filter/' . urlencode($this->_getParam('filter'));

        if ($this->_getParam('search')) {
            $part .= '/search/' . urlencode($this->_getParam('search'));
        }

        if ($this->_getParam('exact')) {
            $part .= '/exact/1';
        }

        if ($this->_getParam('invert')) {
            $part .= '/invert/1';
        }

        if ($this->_getParam('operator')) {
            $part .= '/operator/' . urlencode($this->_getParam('operator'));
        }

        return $part;
    }

}
