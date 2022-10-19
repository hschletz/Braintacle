<?php

/**
 * Hydrator for clients (HARDWARE section)
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

namespace Protocol\Hydrator;

use Model\AbstractModel;

/**
 * Hydrator for clients (HARDWARE section)
 *
 * Unlike with other hydrators, objects are not reset by hydrate(), i.e. data is
 * merged with previous content. Unknown names are ignored.
 */
class ClientsHardware implements \Laminas\Hydrator\HydratorInterface
{
    /**
     * WindowsInstallation prototype
     * @var \Model\Client\WindowsInstallation
     */
    protected $_windowsInstallationPrototype;

    /**
     * Filter for hydration of "OsName"
     *
     * @var \Library\Filter\FixEncodingErrors
     */
    protected $_encodingFilter;

    /**
     * UTC time zone
     *
     * @var \DateTimeZone
     */
    protected $_utcTimeZone;

    /**
     * Map for hydrateName() (client properties only)
     *
     * @var string[]
     */
    protected $_hydratorMapClient = [
        'CHECKSUM' => 'inventoryDiff',
        'DEFAULTGATEWAY' => 'defaultGateway',
        'DESCRIPTION' => 'osComment',
        'DNS' => 'dnsServer',
        'IPADDR' => 'ipAddress',
        'LASTCOME' => 'lastContactDate',
        'LASTDATE' => 'inventoryDate',
        'MEMORY' => 'physicalMemory',
        'NAME' => 'name',
        'OSCOMMENTS' => 'osVersionString',
        'OSNAME' => 'osName',
        'OSVERSION' => 'osVersionNumber',
        'PROCESSORN' => 'cpuCores',
        'PROCESSORS' => 'cpuClock',
        'PROCESSORT' => 'cpuType',
        'SWAP' => 'swapMemory',
        'USERID' => 'userName',
        'UUID' => 'uuid',
    ];

    /**
     * Map for extractName() (client properties only)
     *
     * @var string[]
     */
    protected $_extractorMapClient = [
        'cpuClock' => 'PROCESSORS',
        'cpuCores' => 'PROCESSORN',
        'cpuType' => 'PROCESSORT',
        'defaultGateway' => 'DEFAULTGATEWAY',
        'dnsServer' => 'DNS',
        'inventoryDate' => 'LASTDATE',
        'inventoryDiff' => 'CHECKSUM',
        'ipAddress' => 'IPADDR',
        'lastContactDate' => 'LASTCOME',
        'name' => 'NAME',
        'osComment' => 'DESCRIPTION',
        'osName' => 'OSNAME',
        'osVersionNumber' => 'OSVERSION',
        'osVersionString' => 'OSCOMMENTS',
        'physicalMemory' => 'MEMORY',
        'swapMemory' => 'SWAP',
        'userName' => 'USERID',
        'uuid' => 'UUID',
    ];

    /**
     * Map for hydrateName() (Windows properties only)
     *
     * @var string[]
     */
    protected $_hydratorMapWindows = [
        'ARCH' => 'cpuArchitecture',
        'USERDOMAIN' => 'userDomain',
        'WINCOMPANY' => 'company',
        'WINOWNER' => 'owner',
        'WINPRODID' => 'productId',
        'WINPRODKEY' => 'productKey',
        // WORKGROUP is treated explicitly by hydrate()
    ];

    /**
     * Map for extractName() (Windows properties only)
     *
     * @var string[]
     */
    protected $_extractorMapWindows = [
        'company' => 'WINCOMPANY',
        'cpuArchitecture' => 'ARCH',
        'owner' => 'WINOWNER',
        'productId' => 'WINPRODID',
        'productKey' => 'WINPRODKEY',
        'userDomain' => 'USERDOMAIN',
        // WORKGROUP is treated explicitly by extract()
    ];

    /**
     * Constructor
     *
     * @param \Model\Client\WindowsInstallation $windowsInstallationPrototype
     */
    public function __construct(\Model\Client\WindowsInstallation $windowsInstallationPrototype)
    {
        $this->_windowsInstallationPrototype = $windowsInstallationPrototype;
        $this->_encodingFilter = new \Library\Filter\FixEncodingErrors();
        $this->_utcTimeZone = new \DateTimeZone('UTC');
    }

    /** {@inheritdoc} */
    public function hydrate(array $data, $object)
    {
        $windows = array();
        foreach ($data as $name => $value) {
            $isWindows = isset($this->_hydratorMapWindows[$name]);
            $name = $this->hydrateName($name);
            if ($name) {
                $value = $this->hydrateValue($name, $value);
                if ($isWindows) {
                    $windows[ucfirst($name)] = $value;
                } else {
                    $object->$name = $value;
                }
            }
        }
        // Map WORKGROUP element to appropriate property
        if (isset($data['WINPRODID'])) {
            $windows['Workgroup'] = @$data['WORKGROUP'];
        } else {
            $object->dnsDomain = @$data['WORKGROUP'];
        }

        if ($windows) {
            $object->windows = clone $this->_windowsInstallationPrototype;
            $object->windows->exchangeArray($windows);
        }

        return $object;
    }

    /** {@inheritdoc} */
    public function extract(object $object): array
    {
        $data = array();
        foreach ($object as $name => $value) {
            if ($object instanceof AbstractModel) {
                $name = lcfirst($name);
            }
            $name = $this->extractName($name);
            if ($name) {
                $data[$name] = $this->extractValue($name, $value);
            }
        }
        $windows = $object->windows;
        if ($windows) {
            foreach ($windows as $name => $value) {
                $name = $this->extractName(lcfirst($name));
                if ($name) {
                    $data[$name] = $this->extractValue($name, $value);
                }
            }
            $data['WORKGROUP'] = @$windows['Workgroup'];
        } else {
            $data['WORKGROUP'] = @$object->dnsDomain;
        }
        return $data;
    }

    /**
     * Hydrate name
     *
     * @param string $name
     * @return string|null
     */
    public function hydrateName($name)
    {
        if (isset($this->_hydratorMapClient[$name])) {
            $name = $this->_hydratorMapClient[$name];
        } else {
            $name = @$this->_hydratorMapWindows[$name];
        }
        return $name;
    }

    /**
     * Extract name
     *
     * @param string $name
     * @return string|null
     */
    public function extractName($name)
    {
        if (isset($this->_extractorMapClient[$name])) {
            $name = $this->_extractorMapClient[$name];
        } else {
            $name = @$this->_extractorMapWindows[$name];
        }
        return $name;
    }

    /**
     * Hydrate value
     *
     * @param string $name
     * @param string $value
     * @return mixed
     */
    public function hydrateValue($name, $value)
    {
        switch ($name) {
            case 'inventoryDate':
            case 'lastContactDate':
                $value = \DateTime::createFromFormat('Y-m-d H:i:s', $value, $this->_utcTimeZone);
                break;
            case 'osName':
                $value = $this->_encodingFilter->filter($value);
                break;
        }
        return $value;
    }

    /**
     * Extract value
     */
    public function extractValue($name, $value)
    {
        if ($name == 'LASTCOME' or $name == 'LASTDATE') {
            $value->setTimezone($this->_utcTimeZone);
            $value = $value->format('Y-m-d H:i:s');
        }
        return $value;
    }
}
