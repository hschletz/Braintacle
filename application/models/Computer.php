<?php
/**
 * Class representing a computer
 *
 * $Id$
 *
 * Copyright (C) 2011 Holger Schletz <holger.schletz@web.de>
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
 * @filesource
 */
/**
 * A single computer which is inventoried by OCS agent
 *
 * Properties:
 * - <b>Id:</b> primary key
 * - <b>FullId:</b> full device ID (name + timestamp, like 'COMPUTERNAME-2009-04-27-15-52-37')
 * - <b>Name:</b> computer name
 * - <b>Type:</b> computer type (Desktop, Notebook...) as reported by BIOS
 * - <b>Manufacturer:</b> system manufacturer
 * - <b>Model:</b> system model
 * - <b>Serial:</b> serial number
 * - <b>AssetTag:</b> asset tag
 * - <b>Workgroup:</b> Windows workgroup or domain
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
 * - <b>DnsServer:</b> IP Address of DNS server
 * - <b>DefaultGateway:</b> default gateway
 * - <b>OcsAgent:</b> name and version of OCS agent
 * - <b>OsName:</b> OS name (may be processed by getProperty())
 * - <b>OsVersionNumber:</b> internal OS version number
 * - <b>OsVersionString:</b> OS version (Service pack, kernel version etc...)
 * - <b>OsComment:</b> comment
 * - <b>UserDomain:</b> Domain of current user (for local accounts, this is identical to the computer name)
 * - <b>UserName:</b> User logged in at time of inventory
 * - <b>WindowsCompany:</b> Company name (typed in at installation, Windows only)
 * - <b>WindowsOwner:</b> Owner (typed in at installation, Windows only)
 * - <b>WindowsProductkey:</b> product key, Windows only
 *
 *
 * Properties containing a '.' character refer to child objects. These properties are:
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
 * @package Models
 */
class Model_Computer extends Model_ComputerOrGroup
{
    protected $_propertyMap = array(
        // Values from 'hardware' table
        'Id' => 'id',
        'FullId' => 'deviceid',
        'Name' => 'name',
        'Workgroup' => 'workgroup',
        'CpuClock' => 'processors',
        'CpuCores' => 'processorn',
        'CpuType' => 'processort',
        'InventoryDate' => 'lastdate',
        'LastContactDate' => 'lastcome',
        'PhysicalMemory' => 'memory',
        'SwapMemory' => 'swap',
        'IpAddress' => 'ipaddr',
        'DnsServer' => 'dns',
        'DefaultGateway' => 'defaultgateway',
        'OcsAgent' => 'useragent',
        'OsName' => 'osname',
        'OsVersionNumber' => 'osversion',
        'OsVersionString' => 'oscomments',
        'OsComment' => 'description',
        'UserDomain' => 'userdomain',
        'UserName' => 'userid',
        'WindowsCompany' => 'wincompany',
        'WindowsOwner' => 'winowner',
        'WindowsProductkey' => 'winprodkey',
        // Values from 'bios' table
        'Manufacturer' => 'smanufacturer',
        'Model' => 'smodel',
        'Serial' => 'ssn',
        'Type' => 'type',
        'BiosManufacturer' => 'bmanufacturer',
        'BiosVersion' => 'bversion',
        'BiosDate' => 'bdate',
        'AssetTag' => 'assettag',
        // Values from assigned packages
        'Package.Status' => 'package_status',
        // Values from group memberships
        'Membership' => 'static'
    );

    protected $_types = array(
        'Id' => 'integer',
        'CpuClock' => 'integer',
        'CpuCores' => 'integer',
        'InventoryDate' => 'timestamp',
        'LastContactDate' => 'timestamp',
        'PhysicalMemory' => 'integer',
        'SwapMemory' => 'integer',
        'Membership' => 'enum',
    );

    /**
     * List of all child object types
     * @var array
     */
    private static $_childObjectTypes = array(
        'AudioDevice',
        'Controller',
        'Display',
        'DisplayController',
        'ExtensionSlot',
        'InputDevice',
        'Port',
        'MemorySlot',
        'Modem',
        'NetworkInterface',
        'Printer',
        'Software',
        'StorageDevice',
        'Volume',
    );

    /**
     * Raw properties of child objects from joined queries.
     * @var array
     */
    private $_childProperties = array();


    /** Return a statement object with all computers matching criteria
     * @param array $columns Logical properties to be returned. If empty or null, return all properties.
     * @param string $order Property to sort by
     * @param string $direction One of [asc|desc]
     * @param string|array $filter Name or array of names of a pre-defined filter routine
     * @param string|array $search Search parameter(s) passed to the filter. May be case sensitive depending on DBMS.
     * @param bool|array $exact Force exact match on search parameter(s) (no wildcards, no substrings)
     * @param bool|array $invert Invert query results (return all computers NOT matching criteria)
     * @return Zend_Db_Statement Query result
     */
    static function createStatementStatic(
        $columns=null,
        $order=null,
        $direction='asc',
        $filter=null,
        $search=null,
        $exact=null,
        $invert=null
    )
    {
        $db = Zend_Registry::get('db');

        $dummy = new Model_Computer;
        $map = $dummy->getPropertyMap();

        if (empty($columns)) {
            $columns = array_keys($map); // Select all properties
        }
        foreach ($columns as $column) {
            switch ($column) {
                case 'Manufacturer':
                case 'Model':
                case 'Serial':
                case 'Type':
                case 'BiosManufacturer':
                case 'BiosVersion':
                case 'BiosDate':
                case 'AssetTag':
                    $fromBios[] = $map[$column];
                    break;
                case 'Package.Status':
                case 'Membership':
                    break; // columns are added later
                default:
                    if (array_key_exists($column, $map)) { // ignore nonexistent columns
                        $fromHardware[] = $map[$column];
                    }
            }
        }
        // add PK if not already selected
        if (!in_array('id', $fromHardware)) {
            $fromHardware[] = 'id';
        }

        $select = $db->select()
            ->from('hardware', $fromHardware)
            ->where("deviceid != '_SYSTEMGROUP_'")
            ->where("deviceid != '_DOWNLOADGROUP_'")
            ->order(self::getOrder($order, $direction, $map));
        if (isset($fromBios)) {
            $select->join('bios', 'hardware.id = bios.hardware_id', $fromBios);
        }

        // apply filters
        if (!is_array($filter)) {
            // convert to array if necessary
            $filter = array($filter);
            $search = array($search);
            $exact = array($exact);
            $invert = array($invert);
        }
        foreach ($filter as $index => $type) {
            $arg = $search[$index];
            $matchExact = $exact[$index];
            $invertResult = $invert[$index];
            switch ($type) {
                case '':
                    break; // No filter requested
                case 'Id':
                    $select->where('id = ?', (int) $arg);
                    break;
                case 'Name':
                case 'Workgroup':
                case 'CpuType':
                case 'DnsServer':
                case 'DefaultGateway':
                case 'OcsAgent':
                case 'OsName':
                case 'OsVersionNumber':
                case 'OsVersionString':
                case 'OsComment':
                case 'UserName':
                case 'UserDomain':
                case 'WindowsProductkey':
                case 'Manufacturer':
                case 'Model':
                case 'Serial':
                case 'AssetTag':
                case 'BiosVersion':
                case 'BiosDate':
                    $select = self::_findString($select, 'Computer', $type, $arg, $matchExact, $invertResult);
                    break;
                case 'PackageNonnotified':
                case 'PackageSuccess':
                case 'PackageNotified':
                case 'PackageError':
                    $select = Model_Computer::_filterByPackage($select, $type, $arg);
                    break;
                case 'Software':
                    $select
                        ->distinct()
                        ->join(
                            'softwares',
                            $select->getAdapter()->quoteInto(
                                'hardware.id = softwares.hardware_id AND softwares.name = ?',
                                $search
                            ),
                            array ('software_version' => 'version')
                        );
                    break;
                case 'MemberOf':
                    // $search is expected to be a Model_Group object.
                    $arg->update();

                    $select
                        ->join(
                            'groups_cache',
                            'hardware.id = groups_cache.hardware_id',
                            array('static')
                        )
                        ->where('groups_cache.group_id = ?', $arg->getId())
                        ->where(
                            'groups_cache.static IN (?)',
                            array(
                                Model_GroupMembership::TYPE_DYNAMIC,
                                Model_GroupMembership::TYPE_STATIC
                            )
                        );
                    break;
                default:
                    // If the filter is of the form 'Model.Property', apply a generic string filter.
                    if (!preg_match('/^[a-zA-Z]+\.[a-zA-Z]+$/', $type)) {
                        throw new UnexpectedValueException('Invalid filter: ' . $type);
                    }
                    list($model, $property) = explode('.', $type);
                    $select = self::_findString(
                        $select,
                        $model,
                        $property,
                        $arg,
                        $matchExact,
                        $invertResult
                    );
            }
        }

        return $select->query();
    }

    /**
     * Get a Model_Computer object for the given primary key.
     * @param int $id Primary key
     * @return mixed Fully populated Model_Computer object, FALSE if no computer was found
     */
    static function fetchById($id)
    {
        return Model_Computer::createStatementStatic(null, null, null, 'Id', $id)
            ->fetchObject('Model_Computer');
    }

    /**
     * Retrieve a property by its logical name
     *
     * Mangles certain OS names to a nicer and shorter value.
     * Replaces certain meaningless manufacturer and model names with NULL.
     * Provides access to child object properties.
     */
    function getProperty($property, $rawValue=false)
    {
        if (array_key_exists($property, $this->_childProperties)) {
            // Call setProperty()/getProperty() on child object to enable processing of the value
            list($model, $property) = explode('.', $property);
            $childClass = "Model_$model";
            $childObject = new $childClass;
            $childObject->setProperty($property, $this->_childProperties["$model.$property"]);
            return $childObject->getProperty($property, $rawValue);
        }

        $value = parent::getProperty($property, $rawValue);

        if ($rawValue) {
            return $value;
        }

        switch ($property) {
            case 'OsName':
                // Some Unicode characters to be stripped from OS name
                $r  = chr(0xc2) . chr(0xae); // the (R) symbol
                $tm = chr(0xc2) . chr(0x99); // the TM symbol

                // strip 'Microsoft' prefix to conserve space. We know who made it...
                $value = preg_replace("/Microsoft[$r]* /", '', $this->osname, 1);
                // The TM symbol is not available with certain fonts. Ugly...
                $value = str_replace($tm, '', $value);
                break;
            case 'Manufacturer':
                if ($value == 'To Be Filled By O.E.M.'
                    or $value == 'System manufacturer'
                    or $value == 'System Manufacturer'
                ) {
                    $value = null;
                }
                break;
            case 'Model':
                if ($value == 'To Be Filled By O.E.M.'
                    or $value == 'System Name'
                    or $value == 'System Product Name'
                ) {
                    $value = null;
                }
                break;
        }

        return $value;
    }

    /**
     * Magic method to set a property directly
     *
     * This implementation handles columns from joined tables if they are
     * properly named ('model_property').
     */
    public function __set($property, $value)
    {
        try {
            // Parent's implementation will handle properties from Model_Computer
            parent::__set($property, $value);
        } catch (Exception $exception) {
            // Only handle properly formatted column identifiers
            if (!preg_match('/^[a-z]+_[a-z]+$/', $property)) {
                throw $exception;
            }

            list($model, $property) = explode('_', $property);

            // Since the column identifier is all lowercase, a case insensitive
            // search for a valid child object is necessary. The real class name
            // is determined from $_childObjectTypes.
            foreach (self::$_childObjectTypes as $childModel) {
                if (strcasecmp($model, $childModel) == 0) {
                    // Found the model name. Perform case insensitive search
                    // for the property inside the property map.
                    $childClass = "Model_$childModel";
                    $childObject = new $childClass;
                    foreach (array_keys($childObject->getPropertyMap()) as $childProperty) {
                        if (strcasecmp($property, $childProperty) == 0) {
                            // Property is valid. Store the raw value in $_childProperties.
                            $this->_childProperties["$childModel.$childProperty"] = $value;
                            return; // No further iteration necessary.
                        }
                    }
                }
            }
            throw $exception; // Either model or property is invalid.
        }
    }

    /**
     * Return the datatype of a property
     *
     * This implementation passes unknown properties to their matching child
     * object class if possible.
     */
    public function getPropertyType($property)
    {
        try {
            $type = parent::getPropertyType($property);
        } catch (Exception $exception) {
            // If the property is of the form 'Model.Property', pass it to the
            // given model class. Re-throw exception if this is invalid.
            if (!preg_match('/^[a-zA-Z]+\.[a-zA-Z]+$/', $property)) {
                throw $exception;
            }
            list($model, $property) = explode('.', $property);
            $model = "Model_$model";
            if (!class_exists($model)) {
                throw $exception;
            }
            $model = new $model;
            $type = $model->getPropertyType($property);
        }
        return $type;
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
            return parent::getOrder($order, $direction, $propertyMap);
        } catch (Exception $exception) {
            // Only handle properly formatted identifiers
            if (!preg_match('/^[a-zA-Z]+\.[a-zA-Z]+$/', $order)) {
                throw $exception;
            }
            // Assume proper column alias ('model_property')
            $order = strtolower(strtr($order, '.', '_'));
            if ($direction) {
                $order .= ' ' . $direction;
            }
            return $order;
        }
    }

    /**
     * Get a statement object for all child objects of a given type belonging to this computer.
     *
     * @param string $type Object type to retrieve (name of model class without 'Model_' prefix)
     * @param string $order Property to sort by. If ommitted, the model's builtin default is used.
     * @param string $direction Sorting direction (asc|desc)
     * @return Zend_Db_Statement Statement object with results
     */
    public function getChildObjects($type, $order=null, $direction=null)
    {
        $filters['Computer'] = $this->getId();
        // Apply extra filters.
        if ($type == 'Software' and !Model_Config::getOption('DisplayBlacklistedSoftware')) {
            $filters['Status'] = 'notIgnored';
        }
        $className = "Model_$type";
        $class = new $className;
        return $class->createStatement(
            null,
            $order,
            $direction,
            $filters
        );
    }

    /**
     * Retrieve the user defined fields for this computer.
     * @return array Associative array with field names as keys.
     */
    public function getUserDefinedInfo()
    {
        return Model_UserDefinedInfo::getValues($this->getId());
    }

    /**
     * Set values for the user defined fields for this computer.
     * @param array $values Associative array with field names as keys.
     */
    public function setUserDefinedInfo($values)
    {
        Model_UserDefinedInfo::setValues($this->getId(), $values);
    }

    /**
     * Check if this computer runs any version of Windows
     * @return bool
     */
    public function isWindows()
    {
        return (strpos($this->getOsName(), 'Windows') === 0);
    }

    /**
     * Return TRUE if the serial number or asset tag is blacklisted,
     * i.e. ignored for detection of duplicates.
     *
     * @param string $criteria One of 'Serial' or 'AssetTag'
     * @return bool
     */
    public function isBlacklisted($criteria)
    {
        switch ($criteria) {
            case 'Serial':
                $table = 'blacklist_serials';
                $column = 'serial';
                $value = $this->getSerial();
                break;
            case 'AssetTag':
                $table = 'braintacle_blacklist_assettags';
                $column = 'assettag';
                $value = $this->getAssetTag();
                break;
            default:
                throw new UnexpectedValueException(
                    'Invalid criteria for isBlacklisted(): ' . $criteria
                );
        }

        $db = Zend_Registry::get('db');

        return (bool) $db->fetchOne(
            "SELECT COUNT($column) FROM $table WHERE $column = ?",
            $value
        );
    }

    /** Get long description for a filter
     *
     * @param string $filter Name of a pre-defined filter routine
     * @param string $search Search parameter passed to the filter
     * @param integer $count Number of results for this filter
     * @return string Description ready to be inserted into HTML
     */
    static function getFilterDescription($filter, $search, $count)
    {
        $translate = Zend_Registry::get('Zend_Translate');

        // Multiple filters?
        if (is_array($filter)) {
            if ($filter[0] == 'NetworkInterface.Subnet' and
                $filter[1] == 'NetworkInterface.Netmask'
            ) {
                $description = $translate->_(
                    '%1$d computers with an interface in network \'%2$s\''
                );
                $network = $search[0] . '/' .
                    (32 - log((ip2long($search[1]) ^ 0xffffffff) + 1, 2));

                return htmlspecialchars(sprintf($description, $count, $network));
            }
            // No other multi-filters defined.
            throw new UnexpectedValueException(
                'No description available for this set of multiple filters'
            );
        }

        // Single filter
        switch ($filter) {
            case 'PackageNonnotified':
                $description = $translate->_(
                    '%1$d computers waiting for notification of package \'%2$s\''
                );
                break;
            case 'PackageSuccess':
                $description = $translate->_(
                    '%1$d computers with package \'%2$s\' successfully deployed'
                );
                break;
            case 'PackageNotified':
                $description = $translate->_(
                    '%1$d computers with deployment of package \'%2$s\' in progress'
                );
                break;
            case 'PackageError':
                $description = $translate->_(
                    '%1$d computers where deployment of package \'%2$s\' failed'
                );
                break;
            case 'Software':
                $description = $translate->_(
                    '%1$d computers where software \'%2$s\' is installed'
                );
                break;
            default:
                throw(new Zend_Exception('No description available for filter ' . $filter));
        }
        return htmlspecialchars(sprintf($description, $count, $search));
    }

    /** Apply a package filter.
     *
     * @param Zend_Db_Select Object to apply the filter to
     * @param string $filter Name of a pre-defined filter routine
     * @param string $search Search parameter passed to the filter
     * @return Zend_Db_Select Object with filter applied
     */
    protected static function _filterByPackage(Zend_Db_Select $select, $filter, $search)
    {
        switch ($filter) {
            case 'PackageNonnotified':
                $condition = 'IS NULL';
                break;
            case 'PackageSuccess':
                $condition = '= \'SUCCESS\'';
                break;
            case 'PackageNotified':
                $condition = '= \'NOTIFIED\'';
                break;
            case 'PackageError':
                $condition = 'LIKE \'ERR%\'';
                break;
        }
        return $select->join(
            'devices',
            'hardware.id = devices.hardware_id AND devices.name=\'DOWNLOAD\' AND devices.tvalue ' . $condition,
            array(
                'package_status' => 'tvalue'
            )
        )
        ->join(
            'download_enable',
            'devices.ivalue = download_enable.id',
            null
        )
        ->join(
            'download_available',
            $select->getAdapter()->quoteInto(
                'download_enable.fileid = download_available.fileid AND download_available.name = ?',
                $search
            ),
            null
        );
    }

    /**
     * Apply a filter for a string value.
     *
     * @param Zend_Db_Select Object to apply the filter to
     * @param string $model Name of model class (without 'Model_' prefix) where property can be found.
     * This must be either 'Computer' or a valid child object class. Every
     * other value will trigger an exception.
     * @param string $property Property to search in. Properties unknown to the model will trigger an exception.
     * @param string $arg String to search for
     * @param bool $matchExact Disable wildcards ('*', '?', '%', '_') and substring search.
     * @param bool $invertResult Return computers NOT matching criteria
     * @return Zend_Db_Select Object with filter applied
     */
    protected static function _findString($select, $model, $property, $arg, $matchExact, $invertResult)
    {
        if ($model != 'Computer' and !in_array($model, self::$_childObjectTypes)) {
            throw new UnexpectedValueException('Invalid model: ' . $model);
        }
        // Instantiate a dummy object to retrieve column name from property map
        $className = "Model_$model";
        $class = new $className;
        $column = $class->getColumnName($property);

        // Determine table name and column alias
        if ($model == 'Computer') {
            switch ($property) {
                case 'Manufacturer':
                case 'Model':
                case 'Serial':
                case 'AssetTag':
                case 'BiosVersion':
                case 'BiosDate':
                    $table = 'bios';
                    break;
                default:
                    $table = 'hardware';
            }
            $columnAlias = $column; // Zend_Db_Select will ignore this alias because the strings are identical
        } else {
            $table = $class->getTableName();
            // Compose a column alias to avoid ambiguous identifiers (like
            // 'name' which is present in more than 1 table). This allows
            // identification of the column in a query result.
            // Properties not handled by Model_Computer will be passed to the
            // model class determined from the alias.
            $columnAlias = strtolower($model) . '_' . strtolower($property);
        }

        // Join table if not already present
        if ($table != 'hardware' and !array_key_exists($table, $select->getPart('from'))) {
            $select->join($table, "$table.hardware_id=hardware.id", array());
        }

        // Add column if not already present
        $columnPresent = false;
        foreach ($select->getPart('columns') as $columnPart) {
            if ($columnPart[0] == $table and $columnPart[1] == $column) {
                $columnPresent = true;
            }
        }
        if (!$columnPresent) {
            $select->columns(array($columnAlias => $column), $table);
        }

        // Determine comparision operator and prepare search argument
        if ($matchExact) {
            $operator = '=';
        } else {
            // Replace wildcards '*' and '?' with their SQL counterparts '%' and '_'.
            // If $arg contains '%' and '_', they are currently NOT escaped, i.e. they operate as wildcards too.
            // The result is encapsulated within '%' to support searching for arbitrary substrings.
            $arg = '%' . strtr($arg, '*?', '%_') . '%';
            // String comparisions should be case insensitive if possible.
            // PostgreSQL has the nonstandard ILIKE operator for this.
            // For other DBMS we use the standard LIKE operator, which might be case sensitive depending on
            // implementation.
            if ($select->getAdapter() instanceof Zend_Db_Adapter_Pdo_Pgsql) {
                $operator= 'ILIKE';
            } else {
                $operator= 'LIKE';
            }
        }

        if ($table == 'hardware') {
            // Columns from the 'hardware' table can be queried directly
            if ($invertResult) {
                if ($matchExact) {
                    $operator = '!=';
                } else {
                    $operator = 'NOT ' . $operator;
                }
            }
            $select = $select->where("$table.$column $operator ?", $arg);
        } else {
            // Columns from joined tables can be queried directly except for inverted search.
            // In that case, a subquery has to be built:
            // SELECT ... FROM ... WHERE hardware.id NOT IN (SELECT hardware_id FROM $table WHERE ...);
            if ($invertResult) {
                $subquery = new Zend_Db_Select($select->getAdapter());
                $subquery->from($table, 'hardware_id')
                         ->where("$column $operator ?", $arg);
                $select->where("hardware.id NOT IN ($subquery)");
            } else {
                $select->where("$table.$column $operator ?", $arg);
            }
        }

        return $select;
    }

    /**
     * Delete this computer and all associated child objects from the database
     * @param bool $reuseLock If this instance already has a lock, reuse it.
     * @param string $equivalent Inserted into deleted_equiv table if TraceDeleted is set.
     * @return Success
     */
    public function delete($reuseLock=false, $equivalent=null)
    {
        // A lock is required
        if ((!$reuseLock or !$this->isLocked()) and !$this->lock()) {
            return false;
        }

        $db = Zend_Registry::get('db');
        $id = $this->getId();

        // Get list of tables for child objects
        foreach (self::$_childObjectTypes as $type) {
            $model = 'Model_' . $type;
            $model = new $model;
            $tables[] = $model->getTableName();
        }
        // Additional tables without associated Model_ChildObject class
        $tables[] = 'accesslog';
        $tables[] = 'accountinfo';
        $tables[] = 'bios';
        $tables[] = 'devices';
        $tables[] = 'download_history';
        $tables[] = 'registry'; // No model class defined yet

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
            // Delete rows from child tables
            foreach ($tables as $table) {
                $db->delete($table, array('hardware_id=?' => $id));
            }
            // Delete row in hardware table itself
            $db->delete('hardware', array('id=?' => $id));
            // Insert row into deleted_equiv if configured
            if (Model_Config::getOption('TraceDeleted')) {
                $db->insert(
                    'deleted_equiv',
                    array(
                        'date' => new Zend_Db_Expr('CURRENT_TIMESTAMP'),
                        'deleted' => $this->getFullId(),
                        'equivalent' => $equivalent
                    )
                );
            }
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
     * Find duplicate computers based on different criteria
     *
     * The criteria are: hostname, MAC address, serial number and asset tag.
     * The return value is either the number of duplicates ($count=TRUE) or a
     * Zend_Db_Statement object providing access to Id, Name, MacAddress, Serial
     * AssetTag and LastContactDate ($count=FALSE).
     *
     * @param string $criteria One of Name|MacAddress|Serial|AssetTag
     * @param bool $count Return only number of duplicates instead of full list
     * @param string $order Sorting order (ignored for $count=false)
     * @param string $direction One of asc|desc (ignored for $count=false)
     * @return mixed Number of duplicates or Zend_Db_Statement object, depending on $count
     */
    static function findDuplicates($criteria, $count, $order='Id', $direction='asc')
    {
        $db = Zend_Registry::get('db');
        $select = $db->select();

        // All duplicates are determined by a common method with just some
        // parameters depending on search criteria.
        switch ($criteria) {
            case 'Name':
                $table = 'hardware';
                $column = 'name';
                break;
            case 'MacAddress':
                $table = 'networks';
                $column = 'macaddr';
                $select->where(
                    'macaddr NOT IN(SELECT macaddress FROM blacklist_macaddresses)'
                );
                break;
            case 'Serial':
                $table = 'bios';
                $column = 'ssn';
                $select->where(
                    'ssn NOT IN(SELECT serial FROM blacklist_serials)'
                );
                break;
            case 'AssetTag':
                $table = 'bios';
                $column = 'assettag';
                $select->where(
                    'assettag NOT IN(SELECT assettag FROM braintacle_blacklist_assettags)'
                );
                break;
            default:
                throw new UnexpectedValueException('Invalid criteria: ' . $criteria);
        }

        $select->from($table, $column)
               ->group($column)
               ->having("COUNT($column) > 1");

        if ($count) {
            $outer = $db->select()
                ->from($table, new Zend_Db_Expr("COUNT($column)"))
                ->where("$column IN($select)");

            if ($criteria == 'Name') {
                $outer->where(
                    'deviceid NOT IN(\'_SYSTEMGROUP_\', \'_DOWNLOADGROUP_\')'
                );
            }

            return $outer->query()->fetchColumn();
        } else {
            $dummy = new Model_Computer;
            $map = $dummy->getPropertyMap();

            return $db->select()
                ->from(
                    'hardware',
                    array('id, name, lastcome')
                )
                ->joinLeft(
                    'networks',
                    'networks.hardware_id=hardware.id',
                    array('NetworkInterface_MacAddress' => 'macaddr')
                )
                ->join(
                    'bios',
                    'bios.hardware_id=hardware.id',
                    array('ssn, assettag')
                )
                ->where("$column IN($select)")
                ->order(self::getOrder($order, $direction, $map))
                ->query();
        }
    }

    /**
     * Merge 2 or more computers
     *
     * This method is used to eliminate duplicates in the database. Based on the
     * last contact, the newest entry is preserved. All older entries are
     * deleted. Some information from the older entries can be preserved on the
     * remaining computer.
     *
     * @param array $computers IDs of computers to merge
     * @param bool $mergeUserdefined Preserve user supplied information from old computer
     * @param bool $mergeGroups Preserve manual group assignments from old computers
     * @param bool $mergePackages Preserve package assignments from old computers missing on new computer
     */
    static function mergeComputers($computers, $mergeUserdefined, $mergeGroups, $mergePackages)
    {
        if (is_null($computers)) { // Can happen if no items have been checked
            return;
        }

        if (!is_array($computers)) {
            throw new UnexpectedValueException('mergeComputers() expects array.');
        }

        // $computers may contain duplicate values if a computer has been marked more than once.
        $computers = array_unique($computers);
        if (count($computers) < 2) {
            return;
        }

        $db = Zend_Registry::get('db');
        $db->beginTransaction();
        try {
            // Lock all given computers and create a list sorted by LastContactDate.
            foreach ($computers as $id) {
                $computer = self::fetchById($id);
                if (!$computer or !$computer->lock()) {
                    return;
                }
                $timestamp = $computer->getLastContactDate()->get(Zend_Date::TIMESTAMP);
                $list[$timestamp] = $computer;
            }
            ksort($list);
            // Now that the list is sorted, renumber the indices
            $computers = array_values($list);

            // Newest computer will be the only one not to be deleted, remove it from the list
            $newest = array_pop($computers);

            // Copy the desired data
            if ($mergeUserdefined) {
                // Oldest computer will be the source for merged information
                $oldest = $computers[0];
                $newest->setUserDefinedInfo($oldest->getUserDefinedInfo());
            }

            if ($mergeGroups) {
                // Build list with all manual group assignments from old computers.
                // If more than 1 old computer is to be merged and the computers
                // have different assignments for the same group, the result may
                // me somewhat unpredictable.
                $groupList = array();
                foreach ($computers as $computer) {
                    $groups = $computer->getGroups(Model_GroupMembership::TYPE_MANUAL, null);
                    while ($group = $groups->fetchObject('Model_GroupMembership')) {
                        $groupList[$group->getGroupId()] = $group->getMembership();
                    }
                }
                $newest->setGroups($groupList);
            }

            if ($mergePackages) {
                // The simplest way to merge package assignments is to update
                // the hardware ID directly. If more than 2 computers are to be
                // merged, assignments from all computers are merged, not only
                // from the oldest one.

                // To prevent multiple assignment of the same package to the
                // remaining computer, do not merge packages that are already
                // assigned to the newest computer.
                $subquery = $db->quoteInto(
                    '(SELECT ivalue FROM devices WHERE hardware_id=? AND name=\'DOWNLOAD\')',
                    (int) $newest->getId()
                );
                foreach ($computers as $computer) {
                    $db->update(
                        'devices',
                        array('hardware_id' => $newest->getId()),
                        array(
                            'hardware_id=?' => $computer->getId(),
                            'name=\'DOWNLOAD\'',
                            'ivalue NOT IN ' . $subquery
                        )
                    );
                }
            }

            // Delete all older computers
            foreach ($computers as $computer) {
                $computer->delete(true, $newest->getFullId());
            }
            // Unlock remaining computer
            $newest->unlock();
        } catch (Exception $exception) {
            $db->rollBack();
            throw ($exception);
        }
        $db->commit();
    }

    /**
     * Exclude a MAC address, serial or asset tag from being used as criteria
     * for duplicates search.
     * @param string $criteria One of 'MacAddress', 'Serial' or 'AssetTag'
     * @param string $value Value to be excluded
     */
    static function allowDuplicates($criteria, $value)
    {
        switch ($criteria) {
            case 'MacAddress':
                $table = 'blacklist_macaddresses';
                $column = 'macaddress';
                break;
            case 'Serial':
                $table = 'blacklist_serials';
                $column = 'serial';
                break;
            case 'AssetTag':
                $table = 'braintacle_blacklist_assettags';
                $column = 'assettag';
                break;
            default:
                throw new UnexpectedValueException(
                    'Invalid criteria for allowDuplicates(): ' . $criteria
                );
        }
        $db = Zend_Registry::get('db');
        // Check for existing record to avoid constraint violation
        if (!$db->fetchRow("SELECT $column FROM $table WHERE $column=?", $value)) {
            $db->insert($table, array($column => $value));
        }
    }

    /**
     * Retrieve group membership information for this computer
     * @param integer $membership Membership type to retrieve
     * @param string $order Property to sort by
     * @param string $direction Direction to sort by
     * @return Zend_Db_Statement
     */
    public function getGroups(
        $membership=Model_GroupMembership::TYPE_INCLUDED,
        $order='GroupName',
        $direction='asc'
    )
    {
        return Model_GroupMembership::createStatementStatic(
            $this->getId(),
            $membership,
            $order,
            $direction
        );
    }

    /**
     * Set group membership information for this computer
     *
     * The $newgroups argument is an array with group ID as key and the new
     * membership type as value. Groups which are not present in this array will
     * remain unchanged.
     * @param array $newGroups New group memberships
     */
    public function setGroups($newGroups)
    {
        $id = $this->getId();
        $db = Zend_Registry::get('db');

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
                    Model_Group::fetchById($group)->update(true);
                    break;
            }
        }
    }

}
