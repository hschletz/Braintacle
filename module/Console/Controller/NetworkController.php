<?php

/**
 * Controller for subnets and IP discovery
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
 * Controller for subnets and IP discovery
 */
class NetworkController extends \Laminas\Mvc\Controller\AbstractActionController
{
    /**
     * DeviceManager prototype
     * @var \Model\Network\DeviceManager
     */
    protected $_deviceManager;

    /**
     * Subnet manager
     * @var \Model\Network\SubnetManager
     */
    protected $_subnetManager;

    /**
     * Subnet form prototype
     * @var \Console\Form\Subnet
     */
    protected $_subnetForm;

    /**
     * Device form prototype
     * @var \Console\Form\NetworkDevice
     */
    protected $_deviceForm;

    /**
     * Constructor
     *
     * @param \Model\Network\DeviceManager $deviceManager
     * @param \Model\Network\SubnetManager $subnetManager
     * @param \Console\Form\Subnet $subnetForm
     * @param \Console\Form\NetworkDevice $deviceForm
     */
    public function __construct(
        \Model\Network\DeviceManager $deviceManager,
        \Model\Network\SubnetManager $subnetManager,
        \Console\Form\Subnet $subnetForm,
        \Console\Form\NetworkDevice $deviceForm
    ) {
        $this->_deviceManager = $deviceManager;
        $this->_subnetManager = $subnetManager;
        $this->_subnetForm = $subnetForm;
        $this->_deviceForm = $deviceForm;
    }

    /** {@inheritdoc} */
    public function dispatch(
        \Laminas\Stdlib\RequestInterface $request,
        \Laminas\Stdlib\ResponseInterface $response = null
    ) {
        $this->setActiveMenu('Inventory', 'Network');
        return parent::dispatch($request, $response);
    }

    /**
     * Show overview of devices and subnets
     *
     * @return array 'devices', 'subnets', 'subnetOrder'
     */
    public function indexAction()
    {
        $ordering = $this->getOrder('Name');
        $devices = array();
        foreach ($this->_deviceManager->getTypeCounts() as $description => $count) {
            $devices[] = array('Description' => $description, 'Count' => $count);
        }
        return array(
            'devices' => $devices,
            'subnets' => $this->_subnetManager->getSubnets($ordering['order'], $ordering['direction']),
            'subnetOrder' => $ordering,
        );
    }

    /**
     * Show identified devices
     *
     * Result filtering is controlled by the optional url parameters 'subnet',
     * 'mask' and 'type'.
     *
     * @return array devices, ordering
     */
    public function showidentifiedAction()
    {
        $params = $this->params()->fromQuery();
        $filters = array('Identified' => true);
        if (isset($params['subnet'])) {
            $filters['Subnet'] = $params['subnet'];
        }
        if (isset($params['mask'])) {
            $filters['Mask'] = $params['mask'];
        }
        if (isset($params['type'])) {
            $filters['Type'] = $params['type'];
        }
        $ordering = $this->getOrder('DiscoveryDate', 'desc');
        return array(
            'devices' => $this->_deviceManager->getDevices(
                $filters,
                $ordering['order'],
                $ordering['direction']
            ),
            'ordering' => $ordering,
        );
    }

    /**
     * Show unknown devices
     *
     * Result filtering is controlled by the optional url parameters 'subnet'
     * and 'mask'.
     *
     * @return array devices, ordering
     */
    public function showunknownAction()
    {
        $params = $this->params()->fromQuery();
        $filters = array('Identified' => false);
        if (isset($params['subnet'])) {
            $filters['Subnet'] = $params['subnet'];
        }
        if (isset($params['mask'])) {
            $filters['Mask'] = $params['mask'];
        }
        $ordering = $this->getOrder('DiscoveryDate', 'desc');
        return array(
            'devices' => $this->_deviceManager->getDevices(
                $filters,
                $ordering['order'],
                $ordering['direction']
            ),
            'ordering' => $ordering,
        );
    }

    /**
     * Edit a subnet's properties
     *
     * Query params: subnet, mask
     *
     * @return array|\Laminas\Http\Response array(subnet, form) or redirect response
     */
    public function propertiesAction()
    {
        $params = $this->params();
        $subnet = $this->_subnetManager->getSubnet(
            $params->fromQuery('subnet'),
            $params->fromQuery('mask')
        );

        if ($this->getRequest()->isPost()) {
            $this->_subnetForm->setData($params->fromPost());
            if ($this->_subnetForm->isValid()) {
                $data = $this->_subnetForm->getData();
                $this->_subnetManager->saveSubnet($subnet['Address'], $subnet['Mask'], $data['Name']);
                return $this->redirectToRoute('network', 'index');
            }
        } else {
            $this->_subnetForm->setData(array('Name' => $subnet['Name']));
        }
        return array(
            'subnet' => $subnet,
            'form' => $this->_subnetForm,
        );
    }

    /**
     * Edit a network device
     *
     * Query params: macaddress
     *
     * @return array|\Laminas\Http\Response array(device, form) or redirect response
     */
    public function editAction()
    {
        $params = $this->params();
        try {
            $device = $this->_deviceManager->getDevice($params->fromQuery('macaddress'));
        } catch (\Model\Network\RuntimeException $e) {
            return $this->redirectToRoute('network', 'index');
        }
        if ($this->getRequest()->isPost()) {
            $this->_deviceForm->setData($params->fromPost());
            if ($this->_deviceForm->isValid()) {
                $data = $this->_deviceForm->getData();
                $this->_deviceManager->saveDevice(
                    new \Library\MacAddress($params->fromQuery('macaddress')),
                    $data['Type'],
                    $data['Description']
                );
                return $this->redirectToRoute('network', 'index');
            }
        } else {
            $this->_deviceForm->setData(
                array(
                    'Type' => $device['Type'],
                    'Description' => $device['Description'],
                )
            );
        }
        return array(
            'device' => $device,
            'form' => $this->_deviceForm,
        );
    }

    /**
     * Delete a network device
     *
     * Query params: macaddress
     *
     * @return array|\Laminas\Http\Response array(device) or redirect response
     */
    public function deleteAction()
    {
        $params = $this->params();
        if ($this->getRequest()->isGet()) {
            try {
                $device = $this->_deviceManager->getDevice($params->fromQuery('macaddress'));
                return array('device' => $device);
            } catch (\Model\Network\RuntimeException $e) {
            }
        } else {
            if ($params->fromPost('yes')) {
                $this->_deviceManager->deleteDevice($params->fromQuery('macaddress'));
            }
        }
        return $this->redirectToRoute('network', 'index');
    }
}
