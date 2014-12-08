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
     * Package manager
     * @var \Model\Package\PackageManager
     */
    protected $_packageManager;

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
     * @param \Model\Package\PackageManager $packageManager
     * @param \Model\Config $config
     * @param \Console\Form\Package\Build $buildForm
     * @param \Console\Form\Package\Update $updateForm
     */
    public function __construct(
        \Model\Package\PackageManager $packageManager,
        \Model\Config $config,
        \Console\Form\Package\Build $buildForm,
        \Console\Form\Package\Update $updateForm
    )
    {
        $this->_packageManager = $packageManager;
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
            'packages' => $this->_packageManager->getPackages(
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
        try {
            $oldPackage = $this->_packageManager->getPackage($oldName);
        } catch (\Model\Package\RuntimeException $e) {
            $flashMessenger->addErrorMessage(
                array($this->_("Could not retrieve data from package '%s'.") => $oldName)
            );
            return $this->redirectToRoute('package', 'index');
        }

        $form = $this->_updateForm;
        if ($this->getRequest()->isPost()) {
            $form->setData($this->params()->fromPost() + $this->params()->fromFiles());
            if ($form->isValid()) {
                $data = $form->getData();
                $names = array($oldName, $data['Name']);
                if ($this->_buildPackage($data)) {
                    $newPackage = $this->_packageManager->getPackage($data['Name']);
                    $this->_packageManager->updateAssignments(
                        $oldPackage['EnabledId'],
                        $newPackage['EnabledId'],
                        $data['Deploy']['Nonnotified'],
                        $data['Deploy']['Success'],
                        $data['Deploy']['Notified'],
                        $data['Deploy']['Error'],
                        $data['Deploy']['Groups']
                    );
                    $success = $this->_deletePackage($oldPackage);
                } else {
                    $success = false;
                }
                if ($success) {
                    $flashMessenger->addSuccessMessage(
                        array($this->_('Package \'%s\' was successfully changed to \'%s\'.') => $names)
                    );
                } else {
                    $flashMessenger->addErrorMessage(
                        array($this->_('Error changing Package \'%s\' to \'%s\':') => $names)
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
                    'MaxFragmentSize' => $this->_config->defaultMaxFragmentSize,
                    'Name' => $oldPackage['Name'],
                    'Comment' => $oldPackage['Comment'],
                    'Platform' => $oldPackage['Platform'],
                    'DeployAction' => $oldPackage['DeployAction'],
                    'ActionParam' => $oldPackage['ActionParam'],
                    'Priority' => $oldPackage['Priority'],
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
     * @return bool Success
     */
    protected function _buildPackage($data)
    {
        $data['FileName'] = $data['File']['name'];
        $data['FileLocation'] = $data['File']['tmp_name'];
        $data['FileType'] = $data['File']['type'];

        $flashMessenger = $this->flashMessenger();
        $name = $data['Name'];

        try {
            $this->_packageManager->build($data, true);
            $flashMessenger->addSuccessMessage(
                array($this->_('Package \'%s\' was successfully created.') => $name)
            );
            $flashMessenger->setNamespace('packageName');
            $flashMessenger->addMessage($name);
            return true;
        } catch (\Model\Package\RuntimeException $e) {
            $flashMessenger->addErrorMessage(
                array($this->_('Error creating Package \'%s\':') => $name)
            );
            return false;
        }
    }

    /**
     * Delete a package and send feedback via flashMessenger
     *
     * @param string|\Model_Package $package Package or package name
     * @return bool Success
     */
    protected function _deletePackage($package)
    {
        $flashMessenger = $this->flashMessenger();
        try {
            if (is_string($package)) {
                $package = $this->_packageManager->getPackage($package);
            }
            $name = $package['Name'];
            $this->_packageManager->delete($package);
            $flashMessenger->addSuccessMessage(
                array($this->_('Package \'%s\' was successfully deleted.') => $name)
            );
            return true;
        } catch (\Model\Package\RuntimeException $e) {
            $flashMessenger->addErrorMessage('Package could not be deleted.');
            return false;
        }
    }
}
