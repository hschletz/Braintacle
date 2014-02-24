<?php
/**
 * Controller for subnets and IP discovery
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
 * Controller for subnets and IP discovery
 */
class NetworkController extends \Zend\Mvc\Controller\AbstractActionController
{
    /**
     * Device prototype
     * @var \Model_NetworkDevice
     */
    protected $_device;

    /**
     * DeviceType prototype
     * @var \Model_NetworkDeviceType
     */
    protected $_deviceType;

    /**
     * Subnet prototype
     * @var \Model_Subnet
     */
    protected $_subnet;

    /**
     * Subnet form prototype
     * @var \Form_Subnet
     */
    protected $_subnetForm;

    /**
     * Device form prototype
     * @var \Form_NetworkDevice
     */
    protected $_deviceForm;

    /**
     * Constructor
     *
     * @param \Model_NetworkDevice $device
     * @param \Model_NetworkDeviceType $deviceType
     * @param \Model_Subnet $subnet
     * @param \Form_Subnet $subnetForm
     */
    public function __construct(
        \Model_NetworkDevice $device,
        \Model_NetworkDeviceType $deviceType,
        \Model_Subnet $subnet,
        \Form_Subnet $subnetForm,
        \Form_NetworkDevice $deviceForm
    )
    {
        $this->_device = $device;
        $this->_deviceType = $deviceType;
        $this->_subnet = $subnet;
        $this->_subnetForm = $subnetForm;
        $this->_deviceForm = $deviceForm;
    }

    /** {@inheritdoc} */
    public function dispatch(
        \Zend\Stdlib\RequestInterface $request,
        \Zend\Stdlib\ResponseInterface $response = null
    )
    {
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
        return array(
            'devices' => $this->_deviceType->fetchAll(),
            'subnets' => $this->_subnet->fetchAll($ordering['order'], $ordering['direction']),
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
            'devices' => $this->_device->fetch(
                $filters,
                $ordering['order'],
                $ordering['direction']
            ),
            'ordering' => $ordering,
        );
    }

    /**
     * Show unknows devices
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
            'devices' => $this->_device->fetch(
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
     * @return array|\Zend\Http\Response array(subnet, form) or redirect response
     */
    public function propertiesAction()
    {
        $params = $this->params();
        $subnet = $this->_subnet->create(
            $params->fromQuery('subnet'),
            $params->fromQuery('mask')
        );

        if ($this->getRequest()->isPost()) {
            if ($this->_subnetForm->isValid($params->fromPost())) {
                $subnet['Name'] = $this->_subnetForm->getValue('Name');
                $this->redirectToRoute('network', 'index');
            }
        } else {
            $this->_subnetForm->setDefault('Name', $subnet['Name']);
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
     * @return array|\Zend\Http\Response array(device, form) or redirect response
     */
    public function editAction()
    {
        $params = $this->params();
        $device = $this->_device->fetchByMacAddress($params->fromQuery('macaddress'));
        if ($device) {
            if ($this->getRequest()->isPost()) {
                if ($this->_deviceForm->isValid($params->fromPost())) {
                    $device->fromArray($this->_deviceForm->getValues());
                    $device->save();
                    return $this->redirectToRoute('network', 'index');
                }
            } else {
                foreach ($device as $property => $value) {
                    $this->_deviceForm->setDefault($property, $value);
                }
            }
            return array(
                'device' => $device,
                'form' => $this->_deviceForm,
            );
        } else {
            return $this->redirectToRoute('network', 'index');
        }
    }

    /**
     * Delete a network device
     *
     * Query params: macaddress
     *
     * @return array|\Zend\Http\Response array(device) or redirect response
     */
    public function deleteAction()
    {
        $params = $this->params();
        $device = $this->_device->fetchByMacAddress($params->fromQuery('macaddress'));
        if ($device) {
            if ($this->getRequest()->isGet()) {
                return array('device' => $device);
            } else {
                if ($params->fromPost('yes')) {
                    $device->delete();
                }
            }
        }
        return $this->redirectToRoute('network', 'index');
    }
}
