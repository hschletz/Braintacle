<?php
/**
 * Class representing a computer
 *
 * Copyright (C) 2011-2015 Holger Schletz <holger.schletz@web.de>
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
 *
 * @package Models
 */
/**
 * A single computer which is inventoried by OCS agent
 *
 * Properties:
 *
 * - <b>Id:</b> primary key
 * - <b>ClientId:</b> Client-generated ID (name + timestamp, like 'COMPUTERNAME-2009-04-27-15-52-37')
 * - <b>Name:</b> computer name
 * - <b>Type:</b> computer type (Desktop, Notebook...) as reported by BIOS
 * - <b>Manufacturer:</b> system manufacturer
 * - <b>Model:</b> system model
 * - <b>Serial:</b> serial number
 * - <b>AssetTag:</b> asset tag
 * - <b>CpuClock:</b> CPU clock in MHz
 * - <b>CpuCores:</b> total number of CPUs/cores
 * - <b>CpuType:</b> CPU manufacturer and model
 * - <b>InventoryDate:</b> timestamp of last inventory
 * - <b>LastContactDate:</b> timestamp of last agent contact (may be newer than InventoryDate)
 * - <b>PhysicalMemory:</b> Amount of RAM as reported by OS. May be lower than actual RAM.
 * - <b>SwapMemory:</b> Amount of swap space in use
 * - <b>BiosManufacturer:</b> BIOS manufacturer
 * - <b>BiosVersion:</b> BIOS version
 * - <b>BiosDate:</b> BIOS date
 * - <b>IpAddress:</b> IP Adress
 * - <b>DnsDomain:</b> DNS domain name (UNIX clients only)
 * - <b>DnsServer:</b> IP Address of DNS server
 * - <b>DefaultGateway:</b> default gateway
 * - <b>OcsAgent:</b> name and version of OCS agent
 * - <b>OsName:</b> OS name (may be processed by getProperty())
 * - <b>OsVersionNumber:</b> internal OS version number
 * - <b>OsVersionString:</b> OS version (Service pack, kernel version etc...)
 * - <b>OsComment:</b> comment
 * - <b>UserName:</b> User logged in at time of inventory
 * - <b>Uuid</b> UUID, typically found in virtual machines
 * - <b>Windows:</b> \Model\Client\WindowsInstallation object, NULL for non-Windows systems
 * - <b>CustomFields:</b> \Model\Client\CustomFields object
 * - <b>IsSerialBlacklisted:</b> TRUE if the serial number is blacklisted, i.e. ignored for detection of duplicates.
 * - <b>IsAssetTagBlacklisted:</b> TRUE if the asset tag is blacklisted, i.e. ignored for detection of duplicates.
 * - <b>AudioDevice, Controller, Display, DisplayController, ExtensionSlot,
 *   InputDevice, Port, MemorySlot, Modem, MsOfficeProduct, NetworkInterface,
 *   Printer, RegistryData, Software, StorageDevice, VirtualMachine, Filesystem:</b>
 *   A list of all items of the given type. Equivalent of calling getItems()
 *   without extra arguments.
 *
 * Properties containing a '.' character refer to child objects. These properties are:
 *
 * - <b>Package.Status</b> Deployment status (raw value from download_enable.tvalue)
 *
 *
 * Additionally, properties of child objects from a joined query are accessible
 * too. To make this work, an alias for the column has to be specified in the
 * form 'model_property'. Example:
 * <code>SELECT hardware.name, storages.disksize AS storagedevice_size...</code>
 * This would make the property 'StorageDevice.Size' available to this class.
 * Note that only properties defined by the model class will work.
 * The model prefix ensures that ambiguous properties/columns will not clash.
 *
 * If the 'MemberOf' filter is applied, the <b>Membership</b> property is
 * available which contains one of the {@link Model_GroupMembership} constants.
 *
 * Windows-specific information is available through the public 'windows' member
 * and through the 'Windows.*' property.
 *
 * The properties "Registry.*" refer to the combined value and data of a defined
 * registry value with the given name.
 *
 * @package Models
 */
class Model_Computer extends \Model_Abstract
{
    /**
     * Global cache for _getConfigGroups() results
     *
     * This is a 2-dimensional array: $_configGroups[computer ID][n] = group
     */
    protected static $_configGroups = array();

    /**
     * Global cache for getDefaultConfig() results
     *
     * This is a 2-dimensional array: $_configDefault[computer ID][option name] = value
     */
    protected static $_configDefault = array();

    /**
     * Global cache for getEffectiveConfig() results
     *
     * This is a 2-dimensional array: $_configEffective[computer ID][option name] = value
     */
    protected static $_configEffective = array();

    /**
     * Get assigned packages
     *
     * @param string $order Package assignment property to sort by, default: PackageName
     * @param string $direction asc|desc, default: asc
     * @return \Zend\Db\ResultSet\AbstractResultSet Result set producing \Model\Package\Assignment
     */
    public function getPackages($order='PackageName', $direction='asc')
    {
        $map = array(
            'name' => 'PackageName',
            'tvalue' => 'Status',
            'comments' => 'Timestamp',
        );
        $hydrator = new \Zend\Stdlib\Hydrator\ArraySerializable;
        $hydrator->setNamingStrategy(new \Database\Hydrator\NamingStrategy\MapNamingStrategy($map));
        $hydrator->addStrategy(
            'Timestamp',
            new \Zend\Stdlib\Hydrator\Strategy\DateTimeFormatterStrategy(\Model\Package\Assignment::DATEFORMAT)
        );

        $sql = new \Zend\Db\Sql\Sql(\Library\Application::getService('Db'));
        $select = $sql->select();
        $select->from('devices')
               ->columns(array('tvalue', 'comments'))
               ->join(
                   'download_available',
                   'download_available.fileid = devices.ivalue',
                   array('name'),
                   \Zend\Db\Sql\Select::JOIN_INNER
               )
               ->where(array('hardware_id' => $this['Id'], 'devices.name' => 'DOWNLOAD'))
               ->order(array($hydrator->extractName($order) => $direction));

        $resultSet = new \Zend\Db\ResultSet\HydratingResultSet(
            $hydrator,
            \Library\Application::getService('Model\Package\Assignment')
        );
        $resultSet->initialize($sql->prepareStatementForSqlObject($select)->execute());

        return $resultSet;
    }

    /**
     * Set values for the user defined fields for this computer.
     * @param array $values Associative array with field names as keys.
     */
    public function setUserDefinedInfo($values)
    {
        \Library\Application::getService('Model\Client\CustomFieldManager')->write($this['Id'], $values);
    }

    /**
     * Retrieve group membership information for this computer
     *
     * @param integer $membershipType Membership type to retrieve
     * @param string $order Property to sort by, default: GroupName
     * @param string $direction Direction to sort by, default: asc
     * @return \Model_GroupMembership[]
     */
    public function getGroups(
        $membershipType,
        $order='GroupName',
        $direction='asc'
    )
    {
        return \Library\Application::getService('Model\Group\Membership')->fetch(
            $this,
            $membershipType,
            $order,
            $direction
        );
    }

    /**
     * Set group membership information for this computer (reference groups by name)
     *
     * The $newGroups argument is an array with group names as key and the new
     * membership type as value. Groups which are not present in this array will
     * remain unchanged.
     *
     * @param array $newGroups
     */
    public function setGroupsByName($newGroups)
    {
        $groupsById = array();
        $result = \Library\Application::getService('Db')->query(
            "SELECT id, name FROM hardware WHERE deviceid='_SYSTEMGROUP_'",
            \Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE
        );
        foreach ($result as $group) {
            $name = $group['name'];
            if (isset($newGroups[$name])) {
                $groupsById[$group['id']] = $newGroups[$name];
            }
        }
        $this->setGroups($groupsById);
    }

    /**
     * Set group membership information for this computer (reference groups by ID)
     *
     * The $newgroups argument is an array with group ID as key and the new
     * membership type as value. Groups which are not present in this array will
     * remain unchanged.
     * @param array $newGroups New group memberships
     */
    public function setGroups($newGroups)
    {
        $id = $this->getId();
        $db = Model_Database::getAdapter();

        // Create array with group ID as key and existing membership type as
        // value.
        $oldGroups = $db->fetchPairs(
            'SELECT group_id, static FROM groups_cache WHERE hardware_id = ?',
            $id
        );

        foreach ($newGroups as $group => $newMembership) {
            // If this computer does not match the group's query and has no
            // manual group assignment, the group will not be listed in
            // $oldGroups. In this case, $oldMembership is set to NULL.
            if (isset($oldGroups[$group])) {
                $oldMembership = (int) $oldGroups[$group];
            } else {
                $oldMembership = null;
            }

            // Determine action to be taken depending on old and new membership.
            $action = ''; // default: no action
            switch ($newMembership) {
                case Model_GroupMembership::TYPE_DYNAMIC:
                    if ($oldMembership === Model_GroupMembership::TYPE_STATIC or
                        $oldMembership === Model_GroupMembership::TYPE_EXCLUDED
                    ) {
                        $action = 'delete';
                    }
                    break;
                case Model_GroupMembership::TYPE_STATIC:
                    if ($oldMembership === Model_GroupMembership::TYPE_DYNAMIC or
                        $oldMembership === Model_GroupMembership::TYPE_EXCLUDED
                    ) {
                        $action = 'update';
                    } elseif ($oldMembership === null) {
                        $action = 'insert';
                    }
                    break;
                case Model_GroupMembership::TYPE_EXCLUDED:
                    if ($oldMembership === Model_GroupMembership::TYPE_DYNAMIC or
                        $oldMembership === Model_GroupMembership::TYPE_STATIC
                    ) {
                        $action = 'update';
                    } elseif ($oldMembership === null) {
                        $action = 'insert';
                    }
                    break;
            }

            switch ($action) {
                case 'insert':
                    $db->insert(
                        'groups_cache',
                        array(
                            'hardware_id' => $id,
                            'group_id' => $group,
                            'static' => $newMembership
                        )
                    );
                    break;
                case 'update':
                    $db->update(
                        'groups_cache',
                        array(
                            'static' => $newMembership
                        ),
                        array(
                            'hardware_id = ?' => $id,
                            'group_id = ?' => $group
                        )
                    );
                    break;
                case 'delete':
                    // Delete manual assignment. The group cache will be updated
                    // because this computer may be a candidate for automatic
                    // assignment.
                    $db->delete(
                        'groups_cache',
                        array(
                            'hardware_id = ?' => $id,
                            'group_id = ?' => $group
                        )
                    );
                    $groups = \Library\Application::getService('Model\Group\GroupManager')->getGroups('Id', $group);
                    if ($groups->count()) {
                        $groups->current()->update(true);
                    }
                    break;
            }
        }
    }

    /**
     * Export computer as DOMDocument
     * @return \Protocol\Message\InventoryRequest
     */
    public function toDomDocument()
    {
        $document = new \Protocol\Message\InventoryRequest;
        $document->loadClient($this, \Library\Application::getService('ServiceManager'));
        return $document;
    }

    /**
     * Get all packages from download history
     * @return array Package IDs (creation timestamps)
     */
    public function getDownloadedPackages()
    {
        $db = Model_Database::getAdapter();
        return $db->fetchCol(
            'SELECT pkg_id FROM download_history WHERE hardware_id=? ORDER BY pkg_id',
            $this->getId()
        );
    }

    /** {@inheritdoc} */
    public function getDefaultConfig($option)
    {
        $id = $this->getId();
        if (isset(self::$_configDefault[$id]) and array_key_exists($option, self::$_configDefault[$id])) {
            return self::$_configDefault[$id][$option];
        }

        if ($option == 'allowScan') {
            if ($this->_config->scannersPerSubnet == 0) {
                $value = 0;
            } else {
                $value = 1;
            }
        } else {
            $value = null;
        }
        // Get default from groups.
        $groupValues = array();
        foreach ($this->_getConfigGroups() as $group) {
            $groupValues[] = $group->getConfig($option);
        }
        switch ($option) {
            case 'inventoryInterval':
                $value = $this->_config->inventoryInterval;
                // Special values 0 and -1 always take precedence if
                // configured globally.
                if ($value >= 1) {
                    // Get smallest value of group and global settings
                    foreach ($groupValues as $groupValue) {
                        if ($groupValue !== null and $groupValue < $value) {
                            $value = $groupValue;
                        }
                    }
                }
                break;
            case 'contactInterval':
            case 'downloadMaxPriority':
            case 'downloadTimeout':
                // Get smallest value from groups
                foreach ($groupValues as $groupValue) {
                    if ($value === null or ($groupValue !== null and $groupValue < $value)) {
                        $value = $groupValue;
                    }
                }
                break;
            case 'downloadPeriodDelay':
            case 'downloadCycleDelay':
            case 'downloadFragmentDelay':
                // Get largest value from groups
                foreach ($groupValues as $groupValue) {
                    if ($groupValue > $value) {
                        $value = $groupValue;
                    }
                }
                break;
            case 'packageDeployment':
            case 'scanSnmp':
            case 'allowScan':
                // 0 if global setting or any group setting is 0, otherwise 1.
                if ($option != 'allowScan') { // already initialized for allowScan
                    $value = $this->_config->$option;
                }
                if ($value) {
                    foreach ($groupValues as $groupValue) {
                        if ($groupValue === 0) {
                            $value = 0;
                            break;
                        }
                    }
                }
                break;
        }
        if ($value === null) {
            $value = $this->_config->$option;
        }

        self::$_configDefault[$id][$option] = $value;
        return $value;
    }

    /**
     * Get effective configuration value
     *
     * This method returns the effective setting for an option. It is determined
     * from this computer's individual setting, the global setting and/or all
     * groups of which the computer is a member. The exact rules are:
     *
     * - packageDeployment, allowScan and scanSnmp return 0 if the setting is
     *   disabled either globally, for any group or for the computer,
     *   otherwise 1.
     * - For inventoryInterval, if the global setting is one of the special
     *   values 0 or -1, this setting is returned. Otherwise, return the
     *   smallest value of the group and computer setting. If this is undefined,
     *   use global setting.
     * - contactInterval, downloadMaxPriority and downloadTimeout evaluate (in
     *   that order): the computer setting, the smallest value of all group
     *   settings and the global setting. The first non-null result is returned.
     * - downloadPeriodDelay, downloadCycleDelay, downloadFragmentDelay evaluate
     *   (in that order): the computer setting, the largest value of all group
     *   settings and the global setting. The first non-null result is returned.
     * - For any other setting, the computer's configured value is evaluated via
     *   getConfig().
     *
     * @param string $option Option name
     * @return mixed Effective value or NULL
     */
    public function getEffectiveConfig($option)
    {
        $id = $this->getId();
        if (isset(self::$_configEffective[$id]) and array_key_exists($option, self::$_configEffective[$id])) {
            return self::$_configEffective[$id][$option];
        }

        switch ($option) {
            case 'inventoryInterval':
                $value = $this->_config->inventoryInterval;
                // Special values 0 and -1 always take precedence if configured
                // globally.
                if ($value >= 1) {
                    // Get smallest value of computer and group settings
                    $value = $this->getConfig('inventoryInterval');
                    foreach ($this->_getConfigGroups() as $group) {
                        $groupValue = $group->getConfig('inventoryInterval');
                        if ($value === null or ($groupValue !== null and $groupValue < $value)) {
                            $value = $groupValue;
                        }
                    }
                    // Fall back to global default if not set anywhere else
                    if ($value === null) {
                        $value = $this->_config->inventoryInterval;
                    }
                }
                break;
            case 'contactInterval':
            case 'downloadPeriodDelay':
            case 'downloadCycleDelay':
            case 'downloadFragmentDelay':
            case 'downloadMaxPriority':
            case 'downloadTimeout':
                // Computer value takes precedence.
                $value = $this->getConfig($option);
                if ($value === null) {
                    $value = $this->getDefaultConfig($option);
                }
                break;
            case 'packageDeployment':
            case 'allowScan':
            case 'scanSnmp':
                // If default is 0, return 0.
                // Otherwise override default if explicitly disabled.
                $default = $this->getDefaultConfig($option);
                if ($default and $this->getConfig($option) === 0) {
                    $value = 0;
                } else {
                    $value = $default;
                }
                break;
            default:
                $value = $this->getConfig($option);
        }

        self::$_configEffective[$id][$option] = $value;
        return $value;
    }

    /**
     * Get a list of all groups of which this is computer is a member, suitable for evaluating group config
     *
     * The returned group objects are not fully functional. They contain just
     * enough information to retrieve their configuration. They should not be
     * used for anything else.
     *
     * @return \Model\Group\Group[]
     */
    protected function _getConfigGroups()
    {
        $id = $this->getId();
        if (!isset(self::$_configGroups[$id])) {
            self::$_configGroups[$id] = array();
            $memberships = $this->getGroups(\Model_GroupMembership::TYPE_INCLUDED);
            foreach ($memberships as $membership) {
                $group = clone \Library\Application::getService('Model\Group\Group');
                $group['Id'] = $membership['GroupId'];
                self::$_configGroups[$id][] = $group;
            }
        }
        return self::$_configGroups[$id];
    }

    /** {@inheritdoc} */
    public function getAllConfig()
    {
        $config = parent::getAllConfig();
        $config['Scan']['scanThisNetwork'] = $this->getConfig('scanThisNetwork');
        return $config;
    }
}
