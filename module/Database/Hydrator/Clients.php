<?php

/**
 * Hydrator for clients
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

namespace Database\Hydrator;

/**
 * Hydrator for clients
 *
 * In addition to the column names from the Clients table, the following lowercase names are recognized on hydration:
 *
 * - **package_status**: status of assigned package, added by a package filter. Converted to "Package.Status" property.
 * - **static**: type of a group membership, added by a group filter. Converted to "Membership" property.
 * - **customfields_*column***: custom field, converted to "CustomFields.*Name*" property.
 * - **windows_*column***: Windows property, converted to "Windows.*Property*" property.
 * - **registry_content**: result from a registry filter, converted to "Registry.Content" property.
 * - ***model*.*column***: result from an item filter, converted to "*Model*.*Property*" property.
 */
class Clients implements \Laminas\Hydrator\HydratorInterface
{
    /**
     * Service locator
     * @var \Laminas\ServiceManager\ServiceLocatorInterface
     */
    protected $_serviceLocator;

    /**
     * Filter for hydration of "OsName"
     *
     * @var \Library\Filter\FixEncodingErrors
     */
    protected $_encodingFilter;

    /**
     * Database time zone
     *
     * @var \DateTimeZone
     */
    protected $_databaseTimeZone;

    /**
     * Map for hydrateName() (client properties only)
     *
     * @var string[]
     */
    protected $_hydratorMap = array(
        'id' => 'Id',
        'assettag' => 'AssetTag',
        'bdate' => 'BiosDate',
        'bmanufacturer' => 'BiosManufacturer',
        'bversion' => 'BiosVersion',
        'deviceid' => 'IdString',
        'processors' => 'CpuClock',
        'processorn' => 'CpuCores',
        'processort' => 'CpuType',
        'defaultgateway' => 'DefaultGateway',
        'dns' => 'DnsServer',
        'dns_domain' => 'DnsDomain',
        'lastdate' => 'InventoryDate',
        'checksum' => 'InventoryDiff',
        'ipaddr' => 'IpAddress',
        'lastcome' => 'LastContactDate',
        'smanufacturer' => 'Manufacturer',
        'smodel' => 'ProductName',
        'name' => 'Name',
        'description' => 'OsComment',
        'osname' => 'OsName',
        'osversion' => 'OsVersionNumber',
        'oscomments' => 'OsVersionString',
        'memory' => 'PhysicalMemory',
        'ssn' => 'Serial',
        'swap' => 'SwapMemory',
        'type' => 'Type',
        'useragent' => 'UserAgent',
        'userid' => 'UserName',
        'uuid' => 'Uuid',
    );

    /**
     * Map for extractName() (client properties only)
     *
     * @var string[]
     */
    protected $_extractorMap = array(
        'Id' => 'id',
        'AssetTag' => 'assettag',
        'BiosDate' => 'bdate',
        'BiosManufacturer' => 'bmanufacturer',
        'BiosVersion' => 'bversion',
        'IdString' => 'deviceid',
        'CpuClock' => 'processors',
        'CpuCores' => 'processorn',
        'CpuType' => 'processort',
        'DefaultGateway' => 'defaultgateway',
        'DnsDomain' => 'dns_domain',
        'DnsServer' => 'dns',
        'InventoryDate' => 'lastdate',
        'InventoryDiff' => 'checksum',
        'IpAddress' => 'ipaddr',
        'LastContactDate' => 'lastcome',
        'Manufacturer' => 'smanufacturer',
        'Name' => 'name',
        'OsComment' => 'description',
        'OsName' => 'osname',
        'OsVersionNumber' => 'osversion',
        'OsVersionString' => 'oscomments',
        'PhysicalMemory' => 'memory',
        'ProductName' => 'smodel',
        'Serial' => 'ssn',
        'SwapMemory' => 'swap',
        'Type' => 'type',
        'UserAgent' => 'useragent',
        'UserName' => 'userid',
        'Uuid' => 'uuid',
    );

    /**
     * Constructor
     *
     * @param \Laminas\ServiceManager\ServiceLocatorInterface $serviceLocator
     */
    public function __construct(\Laminas\ServiceManager\ServiceLocatorInterface $serviceLocator)
    {
        $this->_serviceLocator = $serviceLocator;
        $this->_encodingFilter = new \Library\Filter\FixEncodingErrors();
        $this->_databaseTimeZone = new \DateTimeZone('UTC');
    }

    /**
     * Get map of extracted names to hydrated names (only fields from Clients table)
     *
     * @return string[]
     */
    public function getExtractorMap()
    {
        return $this->_extractorMap;
    }

    /** {@inheritdoc} */
    public function hydrate(array $data, $object)
    {
        foreach ($data as $name => $value) {
            $name = $this->hydrateName($name);
            $object->$name = $this->hydrateValue($name, $value);
        }
        return $object;
    }

    /** {@inheritdoc} */
    public function extract(object $object): array
    {
        $data = array();
        foreach ($object as $name => $value) {
            $name = $this->extractName($name);
            $data[$name] = $this->extractValue($name, $value);
        }
        return $data;
    }

    /**
     * Hydrate name
     *
     * @param string $name
     * @return string
     * @throws \DomainException if $name cannot be hydrated
     */
    public function hydrateName($name)
    {
        if (isset($this->_hydratorMap[$name])) {
            return $this->_hydratorMap[$name];
        }

        if ($name == 'package_status') {
            return 'Package.Status';
        }

        if ($name == 'static') {
            return 'Membership';
        }

        if (!preg_match('/^([a-z]+)_([a-z0-9_]+)$/', $name, $matches)) {
            throw new \DomainException('Cannot hydrate name: ' . $name);
        }

        $model = $matches[1];
        $property = $matches[2];
        switch ($model) {
            case 'customfields':
                $hydrator = $this->_serviceLocator->get('Model\Client\CustomFieldManager')->getHydrator();
                $name = 'CustomFields.' . $hydrator->hydrateName($property);
                break;
            case 'windows':
                $hydrator = $this->_serviceLocator->get('Database\Table\WindowsInstallations')->getHydrator();
                $name = 'Windows.' . $hydrator->hydrateName($property);
                break;
            case 'registry':
                $name = 'Registry.Content';
                break;
            default:
                $table = $this->_serviceLocator->get('Model\Client\ItemManager')->getTable($model);
                // Get mixed-case model name
                $model = get_class($table->getResultSetPrototype()->getObjectPrototype());
                $model = substr($model, strrpos($model, '\\') + 1);
                $hydrator = $table->getHydrator();
                $property = $hydrator->hydrateName($property);
                $name = "$model.$property";
        }
        return $name;
    }

    /**
     * Extract name
     *
     * @param string $name
     * @return string
     * @throws \DomainException if $name cannot be extracted
     */
    public function extractName($name)
    {
        if (isset($this->_extractorMap[$name])) {
            return $this->_extractorMap[$name];
        } else {
            throw new \DomainException('Cannot extract name: ' . $name);
        }
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
        if (isset($this->_extractorMap[$name])) {
            switch ($name) {
                case 'InventoryDate':
                case 'LastContactDate':
                    $value = \DateTime::createFromFormat(
                        $this->_serviceLocator->get('Database\Nada')->timestampFormatPhp(),
                        $value,
                        $this->_databaseTimeZone
                    );
                    break;
                case 'OsName':
                    $value = $this->_encodingFilter->filter($value);
                    break;
            }
        } elseif ($name == 'Package.Status') {
            return $value;
        } elseif (preg_match('/^([a-zA-Z]+)\.(.+)/', $name, $matches)) {
            $model = $matches[1];
            $property = $matches[2];
            switch ($model) {
                case 'CustomFields':
                    $hydrator = $this->_serviceLocator->get('Model\Client\CustomFieldManager')->getHydrator();
                    $value = $hydrator->hydrateValue($property, $value);
                    break;
                case 'Windows':
                    $hydrator = $this->_serviceLocator->get('Database\Table\WindowsInstallations')->getHydrator();
                    $value = $hydrator->hydrateValue($property, $value);
                    break;
                case 'Registry':
                    break;
                default:
                    $table = $this->_serviceLocator->get('Model\Client\ItemManager')->getTable($model);
                    $hydrator = $table->getHydrator();
                    $value = $hydrator->hydrateValue($property, $value);
            }
        }
        return $value;
    }

    /**
     * Extract value
     *
     * @param string $name
     * @param mixed $value
     * @return mixed
     */
    public function extractValue($name, $value)
    {
        if ($name == 'lastcome' or $name == 'lastdate') {
            $value->setTimezone($this->_databaseTimeZone);
            $value = $value->format($this->_serviceLocator->get('Database\Nada')->timestampFormatPhp());
        }
        return $value;
    }
}
