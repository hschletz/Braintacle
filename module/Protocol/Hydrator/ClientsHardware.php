<?php
/**
 * Hydrator for clients (HARDWARE section)
 *
 * Copyright (C) 2011-2020 Holger Schletz <holger.schletz@web.de>
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
    protected $_hydratorMapClient = array(
        'CHECKSUM' => 'InventoryDiff',
        'DEFAULTGATEWAY' => 'DefaultGateway',
        'DESCRIPTION' => 'OsComment',
        'DNS' => 'DnsServer',
        'IPADDR' => 'IpAddress',
        'LASTCOME' => 'LastContactDate',
        'LASTDATE' => 'InventoryDate',
        'MEMORY' => 'PhysicalMemory',
        'NAME' => 'Name',
        'OSCOMMENTS' => 'OsVersionString',
        'OSNAME' => 'OsName',
        'OSVERSION' => 'OsVersionNumber',
        'PROCESSORN' => 'CpuCores',
        'PROCESSORS' => 'CpuClock',
        'PROCESSORT' => 'CpuType',
        'SWAP' => 'SwapMemory',
        'USERID' => 'UserName',
        'UUID' => 'Uuid',
    );

    /**
     * Map for extractName() (client properties only)
     *
     * @var string[]
     */
    protected $_extractorMapClient = array(
        'CpuClock' => 'PROCESSORS',
        'CpuCores' => 'PROCESSORN',
        'CpuType' => 'PROCESSORT',
        'DefaultGateway' => 'DEFAULTGATEWAY',
        'DnsServer' => 'DNS',
        'InventoryDate' => 'LASTDATE',
        'InventoryDiff' => 'CHECKSUM',
        'IpAddress' => 'IPADDR',
        'LastContactDate' => 'LASTCOME',
        'Name' => 'NAME',
        'OsComment' => 'DESCRIPTION',
        'OsName' => 'OSNAME',
        'OsVersionNumber' => 'OSVERSION',
        'OsVersionString' => 'OSCOMMENTS',
        'PhysicalMemory' => 'MEMORY',
        'SwapMemory' => 'SWAP',
        'UserName' => 'USERID',
        'Uuid' => 'UUID',
    );

    /**
     * Map for hydrateName() (Windows properties only)
     *
     * @var string[]
     */
    protected $_hydratorMapWindows = array(
        'ARCH' => 'CpuArchitecture',
        'USERDOMAIN' => 'UserDomain',
        'WINCOMPANY' => 'Company',
        'WINOWNER' => 'Owner',
        'WINPRODID' => 'ProductId',
        'WINPRODKEY' => 'ProductKey',
        // WORKGROUP is treated explicitly by hydrate()
    );

    /**
     * Map for extractName() (Windows properties only)
     *
     * @var string[]
     */
    protected $_extractorMapWindows = array(
        'Company' => 'WINCOMPANY',
        'CpuArchitecture' => 'ARCH',
        'Owner' => 'WINOWNER',
        'ProductId' => 'WINPRODID',
        'ProductKey' => 'WINPRODKEY',
        'UserDomain' => 'USERDOMAIN',
        // WORKGROUP is treated explicitly by extract()
    );

    /**
     * Constructor
     *
     * @param \Model\Client\WindowsInstallation $windowsInstallationPrototype
     */
    public function __construct(\Model\Client\WindowsInstallation $windowsInstallationPrototype)
    {
        $this->_windowsInstallationPrototype = $windowsInstallationPrototype;
        $this->_encodingFilter = new \Library\Filter\FixEncodingErrors;
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
                    $windows[$name] = $value;
                } else {
                    $object[$name] = $value;
                }
            }
        }
        // Map WORKGROUP element to appropriate property
        if (isset($data['WINPRODID'])) {
            $windows['Workgroup'] = @$data['WORKGROUP'];
        } else {
            $object['DnsDomain'] = @$data['WORKGROUP'];
        }

        if ($windows) {
            $object['Windows'] = clone $this->_windowsInstallationPrototype;
            $object['Windows']->exchangeArray($windows);
        }

        return $object;
    }

    /** {@inheritdoc} */
    public function extract(object $object): array
    {
        $data = array();
        foreach ($object as $name => $value) {
            $name = $this->extractName($name);
            if ($name) {
                $data[$name] = $this->extractValue($name, $value);
            }
        }
        $windows = $object['Windows'];
        if ($windows) {
            foreach ($windows as $name => $value) {
                $name = $this->extractName($name);
                if ($name) {
                    $data[$name] = $this->extractValue($name, $value);
                }
            }
            $data['WORKGROUP'] = @$windows['Workgroup'];
        } else {
            $data['WORKGROUP'] = @$object['DnsDomain'];
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
            case 'InventoryDate':
            case 'LastContactDate':
                $value = \DateTime::createFromFormat('Y-m-d H:i:s', $value, $this->_utcTimeZone);
                break;
            case 'OsName':
                $value = $this->_encodingFilter->filter($value);
                break;
        }
        return $value;
    }

    /**
     * Extract value
     *
     * @param string $name
     * @param string $value
     * @return mixed
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
