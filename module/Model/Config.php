<?php

/**
 * Application configuration
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

namespace Model;

/**
 * Application configuration
 *
 * This is the interface to the application's configuration. All options are
 * available as magic properties. Property access will directly interact with
 * the config storage in the database if necessary.
 *
 * Already accessed values are cached for the lifetime of the class instance, so
 * that repeated access to an option is inexpensive.
 *
 * The documented datatypes of the properties are only a hint about expected
 * values. Booleans are actually returned as integers (0/1). Integer options are
 * always returned as integer values and safe for strict type checks.
 *
 * @property string $agentWhitelistFile  Server-side path to file with allowed non-OCS agents (FusionInventory etc.)
 * @property integer $autoMergeDuplicates  Detect and merge duplicates automatically. Default: FALSE (recommended)
 * @property string $communicationServerUri  URI of communication server. Default: http://localhost/braintacle-server
 * @property string $defaultAction  Default action for new packages (one of store, execute, launch). Default: launch
 * @property string $defaultActionParam  Default action parameter for new packages
 * @property bool $defaultDeleteInterfaces  Default for deleting network interfaces along with client. Default: true
 * @property bool $defaultDeployError  Default to deploy updated packages with state "error". Default: true
 * @property bool $defaultDeployGroups  Default to deploy updated packages to groups. Default: true
 * @property bool $defaultDeployPending  Default to deploy updated packages with state "pending". Default: true
 * @property bool $defaultDeployRunning  Default to deploy updated packages with state "running". Default: true
 * @property bool $defaultDeploySuccess  Default to deploy updated packages with state "success". Default: true
 * @property integer $defaultMaxFragmentSize  Default maximum fragment size (in kB) for new packages. Default: 0
 * @property bool $defaultMergeConfig  Default for merging client config along with clients. Default: true
 * @property bool $defaultMergeCustomFields  Default for merging userdefined fields along with clients. Default: true
 * @property bool $defaultMergeGroups  Default for merging manual group memberships along with clients. Default: true
 * @property bool $defaultMergePackages  Default for merging package assignments along with clients. Default: false
 * @property bool $defaultMergeProductKey  Default for merging manual Windows product key. Default: true
 * @property integer $defaultPackagePriority  Default priority (0-10) for new packages. Default: 5
 * @property string $defaultPlatform  Default platform for new packages (one of windows, linux, mac). Default: windows
 * @property string $defaultPostInstMessage  Default post-installation message for new packages
 * @property bool $defaultWarn  Default user warning for new packages. Default: false
 * @property bool $defaultWarnAllowAbort  Default to allow abort by user for new packages
 * @property bool $defaultWarnAllowDelay  Default to allow delay by user for new packages
 * @property integer $defaultWarnCountdown  Default warning countdown for new packages
 * @property string $defaultWarnMessage  Default warning message for new packages
 * @property bool $displayBlacklistedSoftware  Display ignored software in client's inventory
 * @property integer $groupCacheExpirationFuzz  Random range added to group cache rebuild interval. Default: 43200
 * @property integer $groupCacheExpirationInterval  Seconds between group cache rebuilds. Default: 43200
 * @property bool $inspectRegistry  Let windows agent inspect configured registry values. Default: true
 * @property bool $inventoryFilter  Evaluate the limitInventory and limitInventoryInterval options. Default: false
 * @property integer $limitInventoryInterval  Minimum seconds between connections.
 * @property integer $lockValidity  Seconds before a client's lock expires. Default: 600
 * @property integer $logLevel  Server logging verbosity (0-2). Default: 0
 * @property string $packageBaseUriHttp  Base URI for package download (HTTP), without http:// prefix
 * @property string $packageBaseUriHttps  Base URI for package download (HTTPS), without https:// prefix
 * @property string $packagePath  Server-side directory where packages are stored
 * @property string $saveDir  Directory where a copy of incoming inventory data is stored
 * @property string $saveFormat  File format for saving: XML (uncompressed) or OCS (zlib). Default: OCS
 * @property bool $saveOverwrite  Overwrite existing files (otherwise, append version to filename). Default: false
 * @property bool $saveRawData  Evaluate saveDir, saveFormat and saveOverwrite options. default: false
 * @property integer $scanArpDelay  Delay in milliseconds (>=10) between single ARP scans. Default: 100
 * @property integer $scannerMaxDays  Maximum days before a client is replaced for scanning. Default: 14
 * @property integer $scannerMinDays  Minimum days before a scanning client is replaced. Default: 1
 * @property integer $scannersPerSubnet  Maximum number of clients per subnet used for scanning. Default: 2
 * @property integer $schemaVersion  Database schema version. Used by the schema manager to check for database states
 *                                   that cannot be determined from the database structure or content alone.
 * @property integer $sessionCleanupInterval  Seconds before a stale server session is expunged. Default: 86400
 * @property bool $sessionRequired  Require full session for inventory. Default: false
 * @property integer $sessionValidity  Maximum server session duration. Default: 3600
 * @property bool $setGroupPackageStatus  Set Client status for group-assigned packages. Default: true
 * @property bool $trustedNetworksOnly  Let server reject clients from untrusted networks. Default: false

 * @property integer $contactInterval  Default hours between agent contacts. Default: 12
 * @property integer $downloadCycleDelay  Default delay in seconds between download cycles. Default: 60
 * @property integer $downloadFragmentDelay  Default delay in seconds between fragment downloads. Default: 60
 * @property integer $downloadMaxPriority  Default maximum priority (0-10) of downloadable packages. Default: 10
 * @property integer $downloadPeriodDelay  Default delay in seconds between download periods. Default: 60
 * @property integer $downloadTimeout  Default download timeout in days. Default: 7
 * @property integer $inventoryInterval  Default days between inventory. 0=always, -1=never. Default: 0
 * @property integer $packageDeployment  Default for enabling/disabling package deployment on agents. Default: false
 * @property bool $scanSnmp  Default SNMP usage for network scanning. Default: true
*/
class Config
{
    /**
     * Default values to be returned if the option is not present in the table
     * @var array
     */
    protected $_defaults = array(
        'communicationServerUri' => 'http://localhost/braintacle-server',
        'defaultAction' => 'launch',
        'defaultDeleteInterfaces' => 1,
        'defaultDeployError' => 1,
        'defaultDeployGroups' => 1,
        'defaultDeployPending' => 1,
        'defaultDeployRunning' => 1,
        'defaultDeploySuccess' => 1,
        'defaultMergeConfig' => 1,
        'defaultMergeCustomFields' => 1,
        'defaultMergeGroups' => 1,
        'defaultMergeProductKey' => 1,
        'defaultPackagePriority' => 5,
        'defaultPlatform' => 'windows',
        'defaultWarn' => 0,
        'validateXml' => 0,
        // Defaults below this point are defined by communication server.
        'autoMergeDuplicates' => 0,
        'contactInterval' => 12,
        'downloadCycleDelay' => 60,
        'downloadFragmentDelay' => 60,
        'downloadMaxPriority' => 10,
        'downloadPeriodDelay' => 60,
        'downloadTimeout' => 7,
        'groupCacheExpirationFuzz' => 43200,
        'groupCacheExpirationInterval' => 43200,
        'inspectRegistry' => 1,
        'inventoryFilter' => 0,
        'inventoryInterval' => 0,
        'lockValidity' => 600,
        'logLevel' => 0,
        'packageDeployment' => 0,
        'saveFormat' => 'OCS',
        'saveOverwrite' => 0,
        'saveRawData' => 0,
        'scanArpDelay' => 100,
        'scannerMaxDays' => 14,
        'scannerMinDays' => 1,
        'scannersPerSubnet' => 2,
        'scanSnmp' => 1,
        'sessionCleanupInterval' => 86400,
        'sessionRequired' => 0,
        'sessionValidity' => 3600,
        'setGroupPackageStatus' => 1,
        'trustedNetworksOnly' => 0,
    );

    /**
     * Option cache
     *
     * This is managed by get() and set().
     * @var array
     **/
    private $_cache = array();

    /**
     * Config table gateway
     * @var \Database\Table\Config
     */
    protected $_config;

    /**
     * Constructor
     *
     * @param \Database\Table\Config $config
     */
    public function __construct(\Database\Table\Config $config)
    {
        $this->_config = $config;
    }

    /**
     * Retrieve the value for a given option
     *
     * @param string $option Option name
     * @return mixed Option value (if set), default value (if defined) or NULL
     */
    public function __get($option)
    {
        if (!isset($this->_cache[$option])) {
            $value = $this->_config->get($option);
            if ($value === null and isset($this->_defaults[$option])) {
                $value = $this->_defaults[$option];
            }
            if ($option == 'autoMergeDuplicates') {
                // Convert bitmask to 0/1
                $value = min($value, 1);
            }
            $this->_cache[$option] = $value;
        }
        return $this->_cache[$option];
    }

    /**
     * Set the value for a given option
     *
     * @param string $option Option name
     * @param mixed $value Option value
     */
    public function __set($option, $value)
    {
        $this->_cache[$option] = $value;
        if ($option == 'autoMergeDuplicates') {
            // Convert boolean to bitmask
            $value = $value ? 63 : 0;
        }
        $this->_config->set($option, $value);
    }

    /**
     * Set multiple options at once
     *
     * Values are written to the database only if different from the current
     * value, including defaults. This allows future changes to defaults to take
     * effect unless manually overridden.
     *
     * @param array|\Traversable $options Associative array or iterator of option names and values
     */
    public function setOptions($options)
    {
        foreach ($options as $name => $value) {
            if ($value != $this->$name) {
                $this->$name = $value;
            }
        }
    }

    /**
     * Return internal database identifier for given option
     *
     * @param string $option Option name
     * @return string internal database identifier
     */
    public function getDbIdentifier($option)
    {
        return $this->_config->getDbIdentifier($option);
    }
}
