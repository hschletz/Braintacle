<?php
/**
 * Controller for all package-related actions.
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

class PackageController extends Zend_Controller_Action
{

    protected $_session;

    public function indexAction()
    {
        $ordering = $this->_helper->ordering('Name');
        $this->view->packages = Model_Package::createStatementStatic(
            $ordering['order'],
            $ordering['direction']
        );
    }

    public function buildAction()
    {
        $form = new Form_Package;

        if ($this->getRequest()->isPost() and $form->isValid($_POST)) {
            $package = new Model_Package;
            $this->_buildPackage($package, $form);
            $this->_helper->redirector('index', 'package');
            return;
        }

        $this->view->form = $form;
    }

    public function deleteAction()
    {
        $name = $this->_getParam('name');

        if ($this->getRequest()->isGet()) {
            $this->_getSession();
            $this->_session->packageName = $name;
            return; // proceed with view script
        }

        if ($this->_getParam('yes')) {
            $package = new Model_Package;
            if ($package->fromName($name)) {
                $this->_deletePackage($package);
            }
        }

        $this->_helper->redirector('index', 'package');
    }

    public function editAction()
    {
        $oldName = $this->_getParam('name');
        $oldPackage = new Model_Package;
        if (!$oldPackage->fromName($oldName)) {
            $this->_setSessionData(
                null,
                false,
                sprintf(
                    $this->view->translate(
                        'Could not retrieve data from package \'%s\'.'
                    ),
                    $oldName
                ),
                $oldPackage->getErrors()
            );

            $this->_helper->redirector('index', 'package');
            return;
        }

        $form = new Form_Package_Edit;

        if ($this->getRequest()->isGet()) {
            // Initialize form upon first usage.
            $form->setValuesFromPackage($oldPackage);
        } elseif ($form->isValid($_POST)) {
            $newName = $form->getValue('Name');
            $newPackage = new Model_Package;
            $success = $this->_buildPackage($newPackage, $form);
            if ($success) {
                $newPackage->updateComputers(
                    $oldPackage,
                    $form->getValue('DeployNonnotified'),
                    $form->getValue('DeploySuccess'),
                    $form->getValue('DeployNotified'),
                    $form->getValue('DeployError'),
                    $form->getValue('DeployGroups')
                );
                $this->_deletePackage($oldPackage);
                $message = sprintf(
                    $this->view->translate(
                        'Package \'%s\' was successfully changed to \'%s\'.'
                    ),
                    $oldName,
                    $newName
                );
            } else {
                $message = sprintf(
                    $this->view->translate(
                        'Error changing Package \'%s\' to \'%s\':'
                    ),
                    $oldName,
                    $newName
                );
            }

            $this->_setSessionData($newName, $success, $message);
            $this->_helper->redirector('index', 'package');
            return;
        }

        $this->view->form = $form;
        Zend_Registry::set('subNavigation', 'Packages');
    }

    protected function _buildPackage($package, $form)
    {
        $package->fromArray($form->getValues());
        $success = $package->build(true);
        $name = $package->getName();

        if ($success) {
            $message = sprintf(
                $this->view->translate(
                    'Package \'%s\' was successfully created.'
                ),
                $name
            );
        } else {
            $message = sprintf(
                $this->view->translate(
                    'Error creating Package \'%s\':'
                ),
                $name
            );
        }

        $this->_setSessionData(
            $name,
            $success,
            $message,
            $package->getErrors()
        );
        return $success;
    }

    protected function _deletePackage($package)
    {
        $name = $package->getName();

        // Check package for valid data
        if ($package->getTimestamp()) {
            $success = $package->delete();
        } else {
            $success = false;
        }

        if ($success) {
            $message = $this->view->translate(
                'Package \'%s\' was succesfully deleted.',
                $name
            );
        } else {
            $message = $this->view->translate(
                'Package \'%s\' could not be deleted.',
                $name
            );
        }

        $this->_setSessionData(
            $name,
            $success,
            $message,
            $package->getErrors()
        );

        return $success;
    }

    protected function _getSession()
    {
        $this->_session = new Zend_Session_Namespace('PackageBuilder');
        $this->_session->setExpirationHops(1);
    }

    protected function _setSessionData($name, $success, $message, $errors=null)
    {
        if (!$this->_session) {
            $this->_getSession();
        }
        $this->_session->packageName = $name;
        $this->_session->success = $success;
        $this->_session->message = $message;
        $this->_session->errors = array_merge(
            (array) ($this->_session->errors),
            (array) $errors
        );
    }

}
