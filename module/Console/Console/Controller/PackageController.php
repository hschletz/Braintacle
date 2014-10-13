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
     * Application config
     * @var \Model\Config
     */
    protected $_config;

    /**
     * Package build form
     * @var \Console\Form\Package\Build
     */
    protected $_buildForm;

    /**
     * Package update form
     * @var \Console\Form\Package\Update
     */
    protected $_updateForm;

    /**
     * Constructor
     *
     * @param \Model_Package $package
     * @param \Model\Config $config
     * @param \Console\Form\Package\Build $buildForm
     * @param \Console\Form\Package\Update $updateForm
     */
    public function __construct(
        \Model_Package $package,
        \Model\Config $config,
        \Console\Form\Package\Build $buildForm,
        \Console\Form\Package\Update $updateForm
    )
    {
        $this->_package = $package;
        $this->_config = $config;
        $this->_buildForm = $buildForm;
        $this->_updateForm = $updateForm;
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
        if ($this->getRequest()->isPost()) {
            $this->_buildForm->setData($this->params()->fromPost() + $this->params()->fromFiles());
            if ($this->_buildForm->isValid()) {
                $this->_buildPackage($this->_buildForm->getData());
                return $this->redirectToRoute('package', 'index');
            }
        } else {
            $this->_buildForm->setData(
                array(
                    'Platform' => $this->_config->defaultPlatform,
                    'DeployAction' => $this->_config->defaultAction,
                    'ActionParam' => $this->_config->defaultActionParam,
                    'Priority' => $this->_config->defaultPackagePriority,
                    'MaxFragmentSize' => $this->_config->defaultMaxFragmentSize,
                    'Warn' => $this->_config->defaultWarn,
                    'WarnMessage' => $this->_config->defaultWarnMessage,
                    'WarnCountdown' => $this->_config->defaultWarnCountdown,
                    'WarnAllowAbort' => $this->_config->defaultWarnAllowAbort,
                    'WarnAllowDelay' => $this->_config->defaultWarnAllowDelay,
                    'PostInstMessage' => $this->_config->defaultPostInstMessage,
                )
            );
        }
        return $this->printForm($this->_buildForm);
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

        $form = $this->_updateForm;
        if ($this->getRequest()->isPost()) {
            $form->setData($this->params()->fromPost() + $this->params()->fromFiles());
            if ($form->isValid()) {
                $data = $form->getData();
                $names = array($oldName, $data['Name']);
                $newPackage = $this->_buildPackage($data);
                if ($newPackage) {
                    $newPackage->updateComputers(
                        $oldPackage,
                        $data['Deploy']['Nonnotified'],
                        $data['Deploy']['Success'],
                        $data['Deploy']['Notified'],
                        $data['Deploy']['Error'],
                        $data['Deploy']['Groups']
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
            }
        } else {
            $this->setActiveMenu('Packages');
            $form->setData(
                array(
                    'Deploy' => array(
                        'Nonnotified' => $this->_config->defaultDeployNonnotified,
                        'Success' => $this->_config->defaultDeploySuccess,
                        'Notified' => $this->_config->defaultDeployNotified,
                        'Error' => $this->_config->defaultDeployError,
                        'Groups' => $this->_config->defaultDeployGroups,
                    ),
                    'Name' => $oldPackage['Name'],
                    'Comment' => $oldPackage['Comment'],
                    'Platform' => $oldPackage['Platform'],
                    'DeployAction' => $oldPackage['DeployAction'],
                    'ActionParam' => $oldPackage['ActionParam'],
                    'Priority' => $oldPackage['Priority'],
                    'MaxFragmentSize' => $this->_config->defaultMaxFragmentSize,
                    'Warn' => $oldPackage['Warn'],
                    'WarnMessage' => $oldPackage['WarnMessage'],
                    'WarnCountdown' => $oldPackage['WarnCountdown'],
                    'WarnAllowAbort' => $oldPackage['WarnAllowAbort'],
                    'WarnAllowDelay' => $oldPackage['WarnAllowDelay'],
                    'PostInstMessage' => $oldPackage['PostInstMessage'],
                )
            );
        }
        return $this->printForm($form);
    }

    /**
     * Build a package and send feedback via flashMessenger
     *
     * @param array $data Package data
     * @return \Model_Package New package on success, NULL on failure
     */
    protected function _buildPackage($data)
    {
        $data['UserActionRequired'] = ($data['PostInstMessage'] != '');
        $data['FileName'] = $data['File']['name'];
        $data['FileLocation'] = $data['File']['tmp_name'];
        $data['FileType'] = $data['File']['type'];

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
