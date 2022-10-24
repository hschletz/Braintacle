<?php

/**
 * Controller for all package related actions
 *
 * Copyright (C) 2011-2022 Holger Schletz <holger.schletz@web.de>
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

use Console\View\Helper\Form\Package\Build;
use Console\View\Helper\Form\Package\Update;

/**
 * Controller for all package related actions
 */
class PackageController extends \Laminas\Mvc\Controller\AbstractActionController
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
    ) {
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
     * @return \Laminas\View\Model\ViewModel|\Laminas\Http\Response form template or redirect response
     */
    public function buildAction()
    {
        if ($this->getRequest()->isPost()) {
            $this->_buildForm->setData($this->params()->fromPost() + $this->params()->fromFiles());
            if ($this->_buildForm->isValid()) {
                $data = $this->_buildForm->getData();
                $data['FileName'] = $data['File']['name'];
                $data['FileLocation'] = $data['File']['tmp_name'];

                $flashMessenger = $this->flashMessenger();
                try {
                    $this->_packageManager->buildPackage($data, true);
                    $flashMessenger->addSuccessMessage(
                        sprintf($this->_("Package '%s' was successfully created."), $data['Name'])
                    );
                    $flashMessenger->addMessage($data['Name'], 'packageName');
                } catch (\Model\Package\RuntimeException $e) {
                    $flashMessenger->addErrorMessage($e->getMessage());
                }
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
        return $this->printForm($this->_buildForm, Build::class);
    }

    /**
     * Delete a package
     *
     * Query params: name
     *
     * @return array|\Laminas\Http\Response array(name) or redirect response
     */
    public function deleteAction()
    {
        $name = $this->params()->fromQuery('name');

        if ($this->getRequest()->isPost()) {
            if ($this->params()->fromPost('yes')) {
                $flashMessenger = $this->flashMessenger();
                try {
                    $this->_packageManager->deletePackage($name);
                    $flashMessenger->addSuccessMessage(
                        sprintf($this->_("Package '%s' was successfully deleted."), $name)
                    );
                } catch (\Model\Package\RuntimeException $e) {
                    $flashMessenger->addErrorMessage($e->getMessage());
                }
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
     * @return \Laminas\View\Model\ViewModel|\Laminas\Http\Response form template or redirect response
     */
    public function updateAction()
    {
        $oldName = $this->params()->fromQuery('name');
        try {
            $package = $this->_packageManager->getPackage($oldName);
        } catch (\Model\Package\RuntimeException $e) {
            $this->flashMessenger()->addErrorMessage($e->getMessage());
            return $this->redirectToRoute('package', 'index');
        }

        $form = $this->_updateForm;
        if ($this->getRequest()->isPost()) {
            $form->setData($this->params()->fromPost() + $this->params()->fromFiles());
            if ($form->isValid()) {
                $data = $form->getData();
                $data['FileName'] = $data['File']['name'];
                $data['FileLocation'] = $data['File']['tmp_name'];
                try {
                    $this->_packageManager->updatePackage(
                        $package,
                        $data,
                        true,
                        $data['Deploy']['Pending'],
                        $data['Deploy']['Running'],
                        $data['Deploy']['Success'],
                        $data['Deploy']['Error'],
                        $data['Deploy']['Groups']
                    );
                    $this->flashMessenger()->addSuccessMessage(
                        sprintf(
                            $this->_('Package \'%1$s\' was successfully changed to \'%2$s\'.'),
                            $oldName,
                            $data['Name']
                        )
                    );
                    $this->flashMessenger()->addMessage($data['Name'], 'packageName');
                } catch (\Model\Package\RuntimeException $e) {
                    $this->flashMessenger()->addErrorMessage(
                        sprintf(
                            $this->_('Error changing Package \'%1$s\' to \'%2$s\': %3$s'),
                            $oldName,
                            $data['Name'],
                            $e->getMessage()
                        )
                    );
                }
                return $this->redirectToRoute('package', 'index');
            }
        } else {
            $this->setActiveMenu('Packages');
            $form->setData(
                array(
                    'Deploy' => array(
                        'Pending' => $this->_config->defaultDeployPending,
                        'Running' => $this->_config->defaultDeployRunning,
                        'Success' => $this->_config->defaultDeploySuccess,
                        'Error' => $this->_config->defaultDeployError,
                        'Groups' => $this->_config->defaultDeployGroups,
                    ),
                    'MaxFragmentSize' => $this->_config->defaultMaxFragmentSize,
                    'Name' => $package['Name'],
                    'Comment' => $package['Comment'],
                    'Platform' => $package['Platform'],
                    'DeployAction' => $package['DeployAction'],
                    'ActionParam' => $package['ActionParam'],
                    'Priority' => $package['Priority'],
                    'Warn' => $package['Warn'],
                    'WarnMessage' => $package['WarnMessage'],
                    'WarnCountdown' => $package['WarnCountdown'],
                    'WarnAllowAbort' => $package['WarnAllowAbort'],
                    'WarnAllowDelay' => $package['WarnAllowDelay'],
                    'PostInstMessage' => $package['PostInstMessage'],
                )
            );
        }
        return $this->printForm($form, Update::class);
    }
}
