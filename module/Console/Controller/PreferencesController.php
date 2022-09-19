<?php

/**
 * Controller for managing preferences
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

/**
 * Controller for managing preferences
 */
class PreferencesController extends \Laminas\Mvc\Controller\AbstractActionController
{
    /**
     * Form manager
     * @var \Laminas\Form\FormElementManager
     */
    protected $_formManager;

    /**
     * CustomFields manager
     * @var \Model\Client\CustomFieldManager
     */
    protected $_customFieldManager;

    /**
     * Network device manager
     * @var \Model\Network\DeviceManager
     */
    protected $_deviceManager;

    /**
     * Registry manager
     * @var \Model\Registry\RegistryManager
     */
    protected $_registryManager;

    /**
     * Application config
     * @var \Model\Config
     */
    protected $_config;

    /**
     * Constructor
     *
     * @param \Laminas\Form\FormElementManager $formManager
     * @param \Model\Client\CustomFieldManager $customFieldManager
     * @param \Model\Network\DeviceManager $deviceManager
     * @param \Model\Registry\RegistryManager $registryManager
     * @param \Model\Config $config
     */
    public function __construct(
        \Laminas\Form\FormElementManager $formManager,
        \Model\Client\CustomFieldManager $customFieldManager,
        \Model\Network\DeviceManager $deviceManager,
        \Model\Registry\RegistryManager $registryManager,
        \Model\Config $config
    ) {
        $this->_formManager = $formManager;
        $this->_customFieldManager = $customFieldManager;
        $this->_deviceManager = $deviceManager;
        $this->_registryManager = $registryManager;
        $this->_config = $config;
    }

    /**
     * Redirect to first page
     *
     * @return \Laminas\Http\Response redirect response
     */
    public function indexAction()
    {
        return $this->redirectToRoute('preferences', 'display');
    }

    /**
     * Show "Display" page
     *
     * @return \Laminas\View\Model\ViewModel|\Laminas\Http\Response View model for "form.php" template or redirect response
     */
    public function displayAction()
    {
        return $this->useForm('Console\Form\Preferences\Display');
    }

    /**
     * Show "Inventory" page
     *
     * @return \Laminas\View\Model\ViewModel|\Laminas\Http\Response View model for "form.php" template or redirect response
     */
    public function inventoryAction()
    {
        return $this->useForm('Console\Form\Preferences\Inventory');
    }

    /**
     * Show "Agent" page
     *
     * @return \Laminas\View\Model\ViewModel|\Laminas\Http\Response View model for "form.php" template or redirect response
     */
    public function agentAction()
    {
        return $this->useForm('Console\Form\Preferences\Agent');
    }

    /**
     * Show "Packages" page
     *
     * @return \Laminas\View\Model\ViewModel|\Laminas\Http\Response View model for "form.php" template or redirect response
     */
    public function packagesAction()
    {
        return $this->useForm('Console\Form\Preferences\Packages');
    }

    /**
     * Show "Download" page
     *
     * @return \Laminas\View\Model\ViewModel|\Laminas\Http\Response View model for "form.php" template or redirect response
     */
    public function downloadAction()
    {
        return $this->useForm('Console\Form\Preferences\Download');
    }

    /**
     * Show "Network scanning" page
     *
     * @return \Laminas\View\Model\ViewModel|\Laminas\Http\Response View model for "form.php" template or redirect response
     */
    public function networkscanningAction()
    {
        return $this->useForm('Console\Form\Preferences\NetworkScanning');
    }

    /**
     * Show "Groups" page
     *
     * @return \Laminas\View\Model\ViewModel|\Laminas\Http\Response View model for "form.php" template or redirect response
     */
    public function groupsAction()
    {
        return $this->useForm('Console\Form\Preferences\Groups');
    }

    /**
     * Show "Raw data" page
     *
     * @return \Laminas\View\Model\ViewModel|\Laminas\Http\Response View model for "form.php" template or redirect response
     */
    public function rawdataAction()
    {
        return $this->useForm('Console\Form\Preferences\RawData');
    }

    /**
     * Show "Filters" page
     *
     * @return \Laminas\View\Model\ViewModel|\Laminas\Http\Response View model for "form.php" template or redirect response
     */
    public function filtersAction()
    {
        return $this->useForm('Console\Form\Preferences\Filters');
    }

    /**
     * Show "System" page
     *
     * @return \Laminas\View\Model\ViewModel|\Laminas\Http\Response View model for "form.php" template or redirect response
     */
    public function systemAction()
    {
        return $this->useForm('Console\Form\Preferences\System');
    }

    /**
     * Provide form to manage custom fields
     *
     * @return array Array(form)
     */
    public function customfieldsAction()
    {
        $form = $this->_formManager->get('Console\Form\DefineFields');
        if ($this->getRequest()->isPost()) {
            $form->setData($this->params()->fromPost());
            if ($form->isValid()) {
                $form->process();
                return $this->redirectToRoute('preferences', 'customfields');
            }
        }
        return array('form' => $form);
    }

    /**
     * Delete a custom field definition
     *
     * URL parameter: 'name'
     * @return array|\Laminas\Http\Response array(field) or redirect response
     */
    public function deletefieldAction()
    {
        $field = $this->params()->fromQuery('name');
        if ($this->getRequest()->isPost()) {
            if ($this->params()->fromPost('yes')) {
                $this->_customFieldManager->deleteField($field);
            }
            return $this->redirectToRoute('preferences', 'customfields');
        } else {
            return array('field' => $field);
        }
    }

    /**
     * Provide form to manage network device types
     *
     * @return array|\Laminas\Http\Response Array(form) or redirect response
     */
    public function networkdevicesAction()
    {
        $form = $this->_formManager->get('Console\Form\NetworkDeviceTypes');
        if ($this->getRequest()->isPost()) {
            $form->setData($this->params()->fromPost());
            if ($form->isValid()) {
                $form->process();
                return $this->redirectToRoute('network', 'index');
            }
        }
        return array('form' => $form);
    }

    /**
     * Delete a network device type definition
     *
     * URL parameter: 'name'
     * @return array|\Laminas\Http\Response Array(description) or redirect response
     */
    public function deletedevicetypeAction()
    {
        if ($this->getRequest()->isPost()) {
            if ($this->params()->fromPost('yes')) {
                $this->_deviceManager->deleteType($this->params()->fromQuery('name'));
            }
            return $this->redirectToRoute('preferences', 'networkdevices');
        } else {
            return array('description' => $this->params()->fromQuery('name'));
        }
    }

    /**
     * Provide form to manage inventoried registry values
     *
     * @return array|\Laminas\Http\Response Array(form) or redirect response
     */
    public function registryvaluesAction()
    {
        $form = $this->_formManager->get('Console\Form\ManageRegistryValues');
        if ($this->getRequest()->isPost()) {
            $form->setData($this->params()->fromPost());
            if ($form->isValid()) {
                $form->process();
                return $this->redirectToRoute('preferences', 'registryvalues');
            }
        }
        return array('form' => $form);
    }

    /**
     * Delete a registry value definition
     *
     * URL parameter: name
     *
     * @return array|\Laminas\Http\Response Array(name) or redirect response
     */
    public function deleteregistryvalueAction()
    {
        if ($this->getRequest()->isPost()) {
            if ($this->params()->fromPost('yes')) {
                $this->_registryManager->deleteValueDefinition($this->params()->fromQuery('name'));
            }
            return $this->redirectToRoute('preferences', 'registryvalues');
        } else {
            return array('name' => $this->params()->fromQuery('name'));
        }
    }

    /**
     * Standard preferences handling via preferences form subclass
     *
     * @param string $name Name of the form service
     * @return \Laminas\View\Model\ViewModel|\Laminas\Http\Response View model for "form.php" template or redirect response
     */
    protected function useForm($name)
    {
        $form = $this->_formManager->get($name);
        if ($this->getRequest()->isPost()) {
            $form->setData($this->params()->fromPost());
            if ($form->isValid()) {
                // Flatten Preferences array, i.e. incorporate fields from a
                // fieldset into a single array.
                $this->_config->setOptions(
                    new \RecursiveIteratorIterator(
                        new \RecursiveArrayIterator($form->getData()['Preferences'])
                    )
                );
                return $this->redirectToRoute(
                    'preferences',
                    $this->getEvent()->getRouteMatch()->getParams()['action']
                );
            }
        } else {
            $preferences = array();
            foreach ($form->get('Preferences') as $element) {
                $name = $element->getName();
                if ($element instanceof \Laminas\Form\Fieldset) {
                    foreach ($element as $subElement) {
                        $subElementName = $subElement->getName();
                        $preferences[$name][$subElementName] = $this->_config->$subElementName;
                    }
                } else {
                    $preferences[$name] = $this->_config->$name;
                }
            }
            $form->setData(array('Preferences' => $preferences));
        }
        return $this->printForm($form);
    }
}
