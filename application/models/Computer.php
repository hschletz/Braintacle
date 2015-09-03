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
     * Windows-specific information
     *
     * Object has undefined content for non-Windows systems.
     * @var \Model\Client\WindowsInstallation
     **/
    public $windows;

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
     * User defined information for this computer
     *
     * It can be 1 of 3 types:
     * 1. A fully populated \Model\Client\CustomFields object
     * 2. An associative array with a subset of available fields
     * 3. NULL if no value has been set yet.
     *
     * It is populated on demand internally. This allows caching the information,
     * efficiently feeding partial information from a query result and making an
     * extra query only if really needed.
     * @var mixed
     */
    private $_userDefinedInfo;

    /**
     * Constructor
     **/
    public function __construct($input=array(), $flags=0, $iteratorClass='ArrayIterator')
    {
        parent::__construct($input, $flags, $iteratorClass);

        // When instantiated from fetchObject(), __set() gets called before the
        // constructor is invoked, which may initialize the property. Don't
        // overwrite it in that case.
        if (!$this->windows) {
            $this->windows = clone \Library\Application::getService('Model\Client\WindowsInstallation');
        };
    }

    /**
     * Retrieve a property by its logical name
     *
     * Provides access to child object properties.
     */
    public function offsetGet($property)
    {
        if (array_key_exists($property, $this)) {
            $value = parent::offsetGet($property);
        } else {
            if ($property == 'Windows') {
                // The OS type is not stored directly in the database. However,
                // the ProductId property is always non-empty on Windows systems
                // so that it can be used to check for a Windows system.
                $windows = $this->getWindows();
                if ($windows['ProductId']) {
                    return $windows;
                } else {
                    return null;
                }
            } elseif ($property == 'CustomFields') {
                return $this->getUserDefinedInfo();
            } elseif (strpos($property, 'Registry.') === 0) {
                return $this['Registry.Content'];
            } elseif ($property == 'IsSerialBlacklisted') {
                return (bool) \Model_Database::getAdapter()->fetchOne(
                    "SELECT COUNT(serial) FROM blacklist_serials WHERE serial = ?",
                    $this['Serial']
                );
            } elseif ($property == 'IsAssetTagBlacklisted') {
                return (bool) \Model_Database::getAdapter()->fetchOne(
                    "SELECT COUNT(assettag) FROM braintacle_blacklist_assettags WHERE assettag = ?",
                    $this['AssetTag']
                );
            } else {
                return $this->getItems($property);
            }
        }

        return $value;
    }

    /**
     * Compose ORDER BY clause from logical identifier
     *
     * This implementation handles properties from child objects if they are
     * properly qualified ('Model.Property').
     */
    static function getOrder($order, $direction, $propertyMap)
    {
        try {
            // Parent's implementation will handle properties from Model_Computer
            $expression = parent::getOrder($order, $direction, $propertyMap);
            if (strpos($expression, 'static ') === 0) {
                $expression = "groups_cache.$expression";
            } elseif ($expression) {
                $expression = "clients.$expression";
            }
            return $expression;
        } catch (Exception $exception) {
            if (preg_match('#^CustomFields\\.(.*)#', $order, $matches)) {
                $hydrator = \Library\Application::getService('Model\Client\CustomFieldManager')->getHydrator();
                $order = 'customfields_' . $hydrator->extractName($matches[1]);
            } elseif (preg_match('/^Windows\\.(.*)/', $order, $matches)) {
                $hydrator = \Library\Application::getService('Database\Table\WindowsInstallations')->getHydrator();
                $order = 'windows_' . $hydrator->extractName($matches[1]);
            } elseif (preg_match('#^Registry\\.#', $order)) {
                $order = 'registry_content';
            } elseif (preg_match('/^([a-zA-Z]+)\.([a-zA-Z]+)$/', $order, $matches)) {
                $model = $matches[1];
                $property = $matches[2];
                // Assume column alias 'model_column'
                $tableGateway = \Library\Application::getService('Model\Client\ItemManager')->getTable($model);
                $column = $tableGateway->getHydrator()->extractName($property);
                $order = strtolower("{$model}_$column");
            } else {
                throw $exception;
            }
            if ($direction) {
                $order .= ' ' . $direction;
            }
            return $order;
        }
    }

    /**
     * Get all items of a given type belonging to this computer.
     *
     * @param string $type Item type to retrieve (name of model class without 'Model_' prefix)
     * @param string $order Property to sort by. If ommitted, the model's builtin default is used.
     * @param string $direction Sorting direction (asc|desc)
     * @param array $filters Extra filters to pass to the model's createStatement() method
     * @return \Zend\Db\ResultSet\AbstractResultSet
     */
    public function getItems($type, $order=null, $direction=null, $filters=array())
    {
        $filters['Client'] = $this['Id'];
        return \Library\Application::getService('Model\Client\ItemManager')->getItems(
            $type, $filters, $order, $direction
        );
    }

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
     * Retrieve the user defined fields for this computer
     *
     * If the $name argument is given, the value for the specific field is
     * returned. If $name is null (the default), a fully populated
     * \Model\Client\CustomFields object is returned.
     * @param string $name Field to retrieve (default: all fields)
     * @return mixed
     * @deprecated superseded by CustomFields property
     */
    public function getUserDefinedInfo($name=null)
    {
        // If _userDefinedInfo is undefined yet, retrieve all fields.
        if (!$this->_userDefinedInfo) {
            $this->_userDefinedInfo = \Library\Application::getService('Model\Client\CustomFieldManager')->read(
                $this['Id']
            );
        }
        // From this point on, _userDefinedInfo is either an array or an object.

        // Always have an object if all fields are requested.
        if (is_null($name)) {
            if (is_array($this->_userDefinedInfo)) {
                $this->_userDefinedInfo = \Library\Application::getService('Model\Client\CustomFieldManager')->read(
                    $this['Id']
                );
            }
            return $this->_userDefinedInfo;
        }

        // isset() would not work here!
        if (is_array($this->_userDefinedInfo) and array_key_exists($name, $this->_userDefinedInfo)) {
            // Requested field is available in the array.
            return $this->_userDefinedInfo[$name];
        } else {
            // Requested field is not available in the array. Create object
            // instead.
            $this->_userDefinedInfo = \Library\Application::getService('Model\Client\CustomFieldManager')->read(
                $this['Id']
            );
        }

        // At this point _userDefinedInfo is always an object.
        return $this->_userDefinedInfo[$name];
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
     * Update windows property for computer
     *
     * It is valid to call this on non-Windows computer objects in which case
     * the content of the object is undefined.
     * @return \Model\Client\WindowsInstallation Updated windows property
     * @deprecated superseded by "Windows" property
     **/
    public function getWindows()
    {
        $windowsInstallations = \Library\Application::getService('Database\Table\WindowsInstallations');
        $select = $windowsInstallations->getSql()->select();
        $select->columns(
            array('workgroup', 'user_domain', 'company', 'owner', 'product_key', 'product_id', 'manual_product_key')
        )->where(array('client_id' => $this['Id']));

        $this->windows = $windowsInstallations->selectWith($select)->current();
        if ($this->windows === false) {
            $this->windows = null;
        }
        return $this->windows;
    }

    /**
     * Delete this computer and all associated child objects from the database
     * @param bool $reuseLock If this instance already has a lock, reuse it.
     * @param bool $deleteInterfaces Delete interfaces from network listing
     * @return bool Success
     */
    public function delete($reuseLock=false, $deleteInterfaces=false)
    {
        // A lock is required
        if ((!$reuseLock or !$this->isLocked()) and !$this->lock()) {
            return false;
        }

        $db = Model_Database::getAdapter();
        $id = $this->getId();

        $tables[] = 'accountinfo';
        $tables[] = 'bios';
        $tables[] = 'braintacle_windows';
        $tables[] = 'download_history';
        $tables[] = 'groups_cache';
        $tables[] = 'itmgmt_comments';
        $tables[] = 'javainfo';
        $tables[] = 'journallog';

        // Start transaction to keep database consistent in case of errors
        // If a transaction is already in progress, an exception will be thrown
        // by PDO which has to be caught. The commit() and rollBack() methods
        // can only be called if the transaction has been started here.
        try{
            $db->beginTransaction();
            $transaction = true;
        } catch (Exception $exception) {
            $transaction = false;
        }

        try {
            // If requested, delete this computer's network interfaces from the
            // list of discovered interfaces. Also delete any manually entered
            // description for these interfaces, if present.
            if ($deleteInterfaces) {
                $db->delete(
                    'netmap',
                    array(
                        'mac IN (SELECT macaddr FROM networks WHERE hardware_id = ?)' => $id
                    )
                );
                $db->delete(
                    'network_devices',
                    array(
                        'macaddr IN (SELECT macaddr FROM networks WHERE hardware_id = ?)' => $id
                    )
                );
            }

            // Delete rows from child tables
            foreach ($tables as $table) {
                $db->delete($table, array('hardware_id=?' => $id));
            }
            \Library\Application::getService('Model\Client\ItemManager')->deleteItems($id);

            // Delete attachments
            $db->delete(
                'temp_files',
                array(
                    'id_dde=?' => $id,
                    'table_name=?' => 'accountinfo'
                )
            );

            // Delete config via ZF2 adapter to avoid deadlock on duplicate merge
            \Library\Application::getService('Database\Table\ClientConfig')->delete(
                array('hardware_id' => $id)
            );

            // Delete row in hardware table itself
            $db->delete('hardware', array('id=?' => $id));
        } catch (Exception $exception) {
            if ($transaction) {
                $db->rollBack();
            }
            throw $exception;
        }

        if ($transaction) {
            $db->commit();
        }

        $this->unlock();
        return true;
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
