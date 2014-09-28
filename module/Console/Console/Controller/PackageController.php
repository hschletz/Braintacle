<?php
/**
 * Controller for all package related actions
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
 * Controller for all package related actions
 */
class PackageController extends \Zend\Mvc\Controller\AbstractActionController
{
    /**
     * Package prototype
     * @var \Model_Package
     */
    protected $_package;

    /**
     * Package build form
     * @var \Form_Package
     */
    protected $_buildForm;

    /**
     * Package edit form
     * @var \Form_Package_Edit
     */
    protected $_editForm;

    /**
     * Constructor
     *
     * @param \Model_Package $package
     */
    public function __construct(
        \Model_Package $package,
        \Form_Package $packageBuild,
        \Form_Package_Edit $packageEdit
    )
    {
        $this->_package = $package;
        $this->_buildForm = $packageBuild;
        $this->_editForm = $packageEdit;
    }

    /**
     * Show package overview
     *
     * @return array array(packages), array(sorting)
     */
    public function indexAction()
    {
        $sorting = $this->getOrder('Name');
        return array(
            'packages' => $this->_package->fetchAll(
                $sorting['order'],
                $sorting['direction']
            ),
            'sorting' => $sorting,
        );
    }

    /**
     * Build a new package.
     *
     * @return \Zend\View\Model\ViewModel|\Zend\Http\Response form template or redirect response
     */
    public function buildAction()
    {
        if ($this->getRequest()->isPost() and $this->_buildForm->isValid($this->params()->fromPost())) {
            $this->_buildPackage($this->_buildForm->getValues());
            return $this->redirectToRoute('package', 'index');
        } else {
            return $this->printForm($this->_buildForm);
        }
    }

    /**
     * Delete a package
     *
     * Query params: name
     *
     * @return array|\Zend\Http\Response array(name) or redirect response
     */
    public function deleteAction()
    {
        $name = $this->params()->fromQuery('name');

        if ($this->getRequest()->isPost()) {
            if ($this->params()->fromPost('yes')) {
                $this->_deletePackage($name);
            }
            return $this->redirectToRoute('package', 'index');
        } else {
            return array('name' => $name);
        }

    }

    /**
     * Update a package
     *
     * Query params: name
     *
     * @return \Zend\View\Model\ViewModel|\Zend\Http\Response form template or redirect response
     */
    public function updateAction()
    {
        $flashMessenger = $this->flashMessenger();

        $oldName = $this->params()->fromQuery('name');
        $oldPackage = clone $this->_package;
        if (!$oldPackage->fromName($oldName)) {
            $flashMessenger->addErrorMessage(
                array("Could not retrieve data from package '%s'." => $oldName)
            );
            return $this->redirectToRoute('package', 'index');
        }

        $form = $this->_editForm;
        if ($this->getRequest()->isPost() and $form->isValid($this->params()->fromPost())) {
            $names = array($oldName, $form->getValue('Name'));
            $newPackage = $this->_buildPackage($form->getValues());
            if ($newPackage) {
                $newPackage->updateComputers(
                    $oldPackage,
                    $form->getValue('DeployNonnotified'),
                    $form->getValue('DeploySuccess'),
                    $form->getValue('DeployNotified'),
                    $form->getValue('DeployError'),
                    $form->getValue('DeployGroups')
                );
                $success = $this->_deletePackage($oldName);
            } else {
                $success = false;
            }
            if ($success) {
                $flashMessenger->addSuccessMessage(
                    array('Package \'%s\' was successfully changed to \'%s\'.' => $names)
                );
            } else {
                $flashMessenger->addErrorMessage(
                    array('Error changing Package \'%s\' to \'%s\':' => $names)
                );
            }
            return $this->redirectToRoute('package', 'index');
        } else {
            $this->setActiveMenu('Packages');
            $form->setValuesFromPackage($oldPackage);
            return $this->printForm($form);
        }
    }

    /**
     * Build a package and send feedback via flashMessenger
     *
     * @param array $data Package data
     * @return \Model_Package New package on success, NULL on failure
     */
    protected function _buildPackage($data)
    {
        $flashMessenger = $this->flashMessenger();

        $package = clone $this->_package;
        $package->fromArray($data);
        $name = $data['Name'];

        if ($package->build(true)) {
            $flashMessenger->addSuccessMessage(
                array('Package \'%s\' was successfully created.' => $name)
            );
            $flashMessenger->setNamespace('packageName');
            $flashMessenger->addMessage($name);
            $returnValue = $package;
        } else {
            $flashMessenger->addErrorMessage(
                array('Error creating Package \'%s\':' => $name)
            );
            $returnValue = null;
        }

        foreach ($package->getErrors() as $message) {
            $flashMessenger->addInfoMessage($message);
        }

        return $returnValue;
    }

    /**
     * Delete a package and send feedback via flashMessenger
     *
     * @param string $name Package name
     * @return bool Success
     */
    protected function _deletePackage($name)
    {
        $flashMessenger = $this->flashMessenger();
        $package = clone $this->_package;

        // Check package for valid data
        if ($package->fromName($name)) {
            $success = $package->delete();
        } else {
            $success = false;
        }
        if ($success) {
            $flashMessenger->addSuccessMessage(
                array('Package \'%s\' was successfully deleted.' => $name)
            );
        } else {
            $flashMessenger->addErrorMessage(
                array('Package \'%s\' could not be deleted.' => $name)
            );
        }
        foreach ($package->getErrors() as $message) {
            $flashMessenger->addInfoMessage($message);
        }

        return $success;
    }
}
