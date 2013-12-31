<?php
/**
 * Controller for all computer-related actions.
 *
 * Copyright (C) 2011-2013 Holger Schletz <holger.schletz@web.de>
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
        // Join columns that have been split at an escaped comma
        $columns = $this->_decodeColumns($columns);

        // unescape backslashes
        $this->view->columns = array();
        foreach ($columns as $column) {
            $this->view->columns[] = strtr($column, array('\\\\' => '\\'));
        }
        $this->view->computers = Model_Computer::createStatementStatic(
            $this->view->columns,
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
            $this->view->filterUriParams = $this->getFilterUriParams();
        }
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
        $form = new Form_Search;

        if ($this->getRequest()->isPost()) {
            if ($form->isValid($_POST)) {
                // Request minimal column list and add columns for pattern or inverted searches
                $columns = array('Name', 'UserName', 'InventoryDate');
                $encoder = new Braintacle_Filter_ColumnListEncode;
                if ($form->getValue('invert') or !$form->getValue('exact')) {
                    $filter = $encoder->filter($form->getValue('filter'));
                    if (!in_array($filter, $columns)) {
                        $columns[] = $filter;
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
                $this->_helper->redirector(
                    'index',
                    'computer',
                    null,
                    $this->getFilterUriParams() + array(
                        'customFilter' => '1',
                        'columns' => implode(',', $columns),
                    )
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

    /**
     * Return request parameters that define the current filter
     *
     * To keep generated URIs short, only non-empty, non-default parameters are
     * returned.
     * @return array
     */
    public function getFilterUriParams()
    {
        $params = array();
        if (!$this->_getParam('filter')) {
            return $params;
        }
        $params['filter'] = $this->_getParam('filter');

        if ($this->_getParam('search')) {
            $params['search'] = $this->_getParam('search');
        }

        if ($this->_getParam('exact')) {
            $params['exact'] = '1';
        }

        if ($this->_getParam('invert')) {
            $params['invert'] = '1';
        }

        if ($this->_getParam('operator')) {
            $params['operator'] = $this->_getParam('operator');
        }

        return $params;
    }

    /**
     * @ignore
     * This is called as part of the decoding process for comma-separated column
     * lists.
     * @param array $columns List parts, result from explode()ing the list
     * @return array List parts with reconstructed commas, but literal backslashes still escaped.
     */
    protected function _decodeColumns($columns)
    {
        foreach ($columns as $index => $column) {
            // Check for trailing backslashes
            if (preg_match('#(\\\\+)$#', $column, $matches)) {
                // If the number of trailing backslashes is odd, the last
                // backslash escapes a comma. All other backslashes in this
                // block are escaped backslashes (\\).
                if (strlen($matches[1]) % 2) {
                    // Strip only the last backslash, add the comma that got
                    // swallowed by explode() and concatenate the next element
                    // which is part of the original string.
                    $columns[$index] = substr($column, 0, -1) . ',' . $columns[$index + 1];
                    unset($columns[$index + 1]);
                    // Try again with rearranged consecutive indices.
                    return $this->_decodeColumns(array_values($columns));
                }
            }
        }
        // No more concatenation to be done. Return original array.
        return $columns;
    }
}
