<?php
/**
 * Class for retrieving and setting options from the config table
 *
 * $Id$
 *
 * Copyright (C) 2011-2013 Holger Schletz <holger.schletz@web.de>
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
 * Class for retrieving and setting options from the config table
 *
 * All access to the config table should happen through this class via
 * {@link get()} ans {@link set()}. {@link $optionMap} defines mappings from
 * logical option names (which are used by these methods) to the names actually
 * stored in the table.
 *
 * This class can optionally provide default values for options not present
 * in the table. These defaults are defined in {@link $_defaults}.
 *
 * For convenience, all methods are static, so that you don't have to
 * instantiate an object everytime you just want to retrieve a single value.
 * @package Models
 */
class Model_Config
{
    /**
     * Mapping from logical option names to actual names in the table
     * @var array
     */
    static $optionMap = array(
        // Database schema version (latest version in Braintacle_SchemaManager::SCHEMA_VERSION)
        'SchemaVersion' => 'BRAINTACLE_SCHEMA_VERSION',
        // Defaults for package builder
        'DefaultPackagePriority' => 'BRAINTACLE_DEFAULT_PACKAGE_PRIORITY',
        'DefaultMaxFragmentSize' => 'BRAINTACLE_DEFAULT_MAX_FRAGMENT_SIZE',
        'DefaultPlatform' => 'BRAINTACLE_DEFAULT_PLATFORM',
        'DefaultInfoFileLocation' => 'BRAINTACLE_DEFAULT_INFOFILE_LOCATION',
        'DefaultDownloadLocation' => 'BRAINTACLE_DEFAULT_DOWNLOAD_LOCATION',
        'DefaultCertificate' => 'BRAINTACLE_DEFAULT_CERTIFICATE',
        'DefaultAction' => 'BRAINTACLE_DEFAULT_ACTION',
        'DefaultActionParam' => 'BRAINTACLE_DEFAULT_ACTION_PARAM',
        'DefaultWarn' => 'BRAINTACLE_DEFAULT_WARN',
        'DefaultWarnMessage' => 'BRAINTACLE_DEFAULT_WARN_MESSAGE',
        'DefaultWarnCountdown' => 'BRAINTACLE_DEFAULT_WARN_COUNTDOWN',
        'DefaultWarnAllowAbort' => 'BRAINTACLE_DEFAULT_WARN_ALLOW_ABORT',
        'DefaultWarnAllowDelay' => 'BRAINTACLE_DEFAULT_WARN_ALLOW_DELAY',
        'DefaultUserActionRequired' => 'BRAINTACLE_DEFAULT_USER_ACTION_REQUIRED',
        'DefaultUserActionMessage' => 'BRAINTACLE_DEFAULT_USER_ACTION_MESSAGE',
        // Defaults for modifying packages
        'DefaultDeployNonnotified' => 'BRAINTACLE_DEFAULT_DEPLOY_NONNOTIFIED',
        'DefaultDeploySuccess' => 'BRAINTACLE_DEFAULT_DEPLOY_SUCCESS',
        'DefaultDeployNotified' => 'BRAINTACLE_DEFAULT_DEPLOY_NOTIFIED',
        'DefaultDeployError' => 'BRAINTACLE_DEFAULT_DEPLOY_ERROR',
        'DefaultDeployGroups' => 'BRAINTACLE_DEFAULT_DEPLOY_GROUPS',
        // Defaults for merging duplicates
        'DefaultMergeUserdefined' => 'BRAINTACLE_DEFAULT_MERGE_USERDEFINED',
        'DefaultMergeGroups' => 'BRAINTACLE_DEFAULT_MERGE_GROUPS',
        'DefaultMergePackages' => 'BRAINTACLE_DEFAULT_MERGE_PACKAGES',
        // Default for deleting interfaces together with a computer
        'DefaultDeleteInterfaces' => 'BRAINTACLE_DEFAULT_DELETE_INTERFACES',
        // Should blacklisted software be displayed in computer and software inventory?
        'DisplayBlacklistedSoftware' => 'BRAINTACLE_DISPLAY_BLACKLISTED_SOFTWARE',
        // OCS options. The list is incomplete; only options used by Braintacle are listed.
        'PackagePath' => 'DOWNLOAD_PACK_DIR', // get() will append '/download/'
        'LockValidity' => 'LOCK_REUSE_TIME', // Seconds (>=1) before a computer's lock expires
        'TraceDeleted' => 'TRACE_DELETED', // Use deleted_equiv table
        'GroupCacheExpirationInterval' => 'GROUPS_CACHE_REVALIDATE', // Seconds (>=1) between cache rebuilds
        'GroupCacheExpirationFuzz' => 'GROUPS_CACHE_OFFSET', // Random range (>=1) added to interval
        'CommunicationServerAddress' => 'LOCAL_SERVER', // DEPRECATED: Hostname/address of communication server
        'CommunicationServerPort' => 'LOCAL_PORT', // DEPRECATED: Port number of communication server
        'CommunicationServerUri' => 'LOCAL_URI_SERVER', // URI of communication server
        // Options below this point are used by the communication server only.
        // Braintacle only uses them in the preferences dialogs.
        'UseGroups' => 'ENABLE_GROUPS', // Use group feature
        'SetGroupPackageStatus' => 'DOWNLOAD_GROUPS_TRACE_EVENTS', // Set computer status for group-assigned packages
        'InspectRegistry' => 'REGISTRY', // Turn inventory of configured registry keys on or off
        'AgentDeployment' => 'DEPLOY', // DEPRECATED: Turn automatic agent deployment on or off
        'AgentUpdate' => 'UPDATE', // DEPRECATED: Turn automatic agent update on or off
        'PackageDeployment' => 'DOWNLOAD', // Turn package deployment on or off
        'ScannersPerSubnet' => 'IPDISCOVER', // Maximum number of computers per subnet used for scanning
        'ScanSnmp' => 'SNMP', // Use SNMP for scanning
        'ScannerMinDays' => 'IPDISCOVER_BETTER_THRESHOLD', // Minimum days (>=1) before a scanning computer is replaced
        'ScannerMaxDays' => 'IPDISCOVER_MAX_ALIVE', // Maximum days (>=1) before a computer is replaced for scanning
        'ScanAlways' => 'IPDISCOVER_NO_POSTPONE', // Scan immediately, even if no computer qualifies for scanning
        'ScanningConfigurationInGroups' => 'IPDISCOVER_USE_GROUPS', // Use scanning configuration in groups
        'ScanArpDelay' => 'IPDISCOVER_LATENCY', // Delay in milliseconds (>=10) between single ARP scans
        'SaveRawData' => 'GENERATE_OCS_FILES', // Save incoming raw XML data to files
        'SaveDir' => 'OCS_FILES_PATH', // Directory where to save files
        'SaveFormat' => 'OCS_FILES_FORMAT', // File format for saving: xml (uncompressed) or ocs (compressed)
        'SaveOverwrite' => 'OCS_FILES_OVERWRITE', // Overwrite existing files (otherwise, append version to filename)
        'TrustedNetworksOnly' => 'PROLOG_FILTER_ON', // Reject clients from untrusted networks
        'InventoryFilter' => 'INVENTORY_FILTER_ON', // Enable inventory filter (prerequisite for the next 2 options)
        'LimitInventory' => 'INVENTORY_FILTER_FLOOD_IP', // Limit inventory processing from a particular IP address
        'LimitInventoryInterval' => 'INVENTORY_FILTER_FLOOD_IP_CACHE_TIME', // Number of seconds between connections
        'CustomProcessing' => 'INVENTORY_FILTER_ENABLED', // DISCOURAGED: Use custom inventory processing routine
        'SessionValidity' => 'SESSION_VALIDITY_TIME', // Seconds (>=1) a session with the communication server is held
        'SessionCleanupInterval' => 'SESSION_CLEAN_TIME', // Seconds (>=1) before a stale session is expunged
        'SessionRequired' => 'INVENTORY_SESSION_ONLY', // Require full session for inventory
        'LogPath' => 'LOGPATH', // Path to logfiles
        'LogLevel' => 'LOGLEVEL', // Logging verbosity (0-2)
        'AutoDuplicateCriteria' => 'AUTO_DUPLICATE_LVL', // DISCOURAGED: Bitmask for automatic duplicate resolution
        'UpdateChangedSectionsOnly' => 'INVENTORY_DIFF', // Update only changed inventory sections
        'UpdateChangedSnmpSectionsOnly' => 'SNMP_INVENTORY_DIFF', // Update only changed SNMP sections
        'UseDifferentialUpdate' => 'INVENTORY_WRITE_DIFF', // Use differential inventory updates (row level)
        'UseTransactions' => 'INVENTORY_TRANSACTION', // RECOMMENDED: Use database transactions
        'UseCacheTables' => 'INVENTORY_CACHE_ENABLED', // DISCOURAGED: use cache tables
        'KeepObsoleteCacheItems' => 'INVENTORY_CACHE_KEEP', // Don't delete obsolete items from cache
        'CacheTableExpirationinterval' => 'INVENTORY_CACHE_REVALIDATE', // Days (>=1) between cache rebuilds
        'AcceptNonZlib' => 'COMPRESS_TRY_OTHERS', // RECOMMENDED: Accept requests other than raw zlib compressed
        'AgentWhitelistFile' => 'EXT_USERAGENTS_FILE_PATH', // File with allowed non-OCS agents (FusionInventory etc.)
        // Options below this point can be overridden individually for single
        // computers or groups.
        'ContactInterval' => 'PROLOG_FREQ', // Hours between agent contacts (>=1)
        'InventoryInterval' => 'FREQUENCY', // Days between inventory. 0=always, -1=never
        'DownloadPeriodDelay' => 'DOWNLOAD_PERIOD_LATENCY', // Delay in seconds (>=1) between 2 download periods
        'DownloadCycleDelay' => 'DOWNLOAD_CYCLE_LATENCY', // Delay in seconds (>=1) between 2 download cycles
        'DownloadFragmentDelay' => 'DOWNLOAD_FRAG_LATENCY', // Delay in seconds (>=1) between 2 fragment downloads
        'DownloadMaxPriority' => 'DOWNLOAD_PERIOD_LENGTH', // Maximum priority (0-10) of downloadable packages
        'DownloadTimeout' => 'DOWNLOAD_TIMEOUT', // Download timeout in days (>=1)
    );

    /**
     * Default values to be returned if the option is not present in the table
     * @var array
     */
    protected static $_defaults = array(
        'DefaultPackagePriority' => '5',
        'DefaultMaxFragmentSize' => '0',
        'DefaultPlatform' => 'windows',
        'DefaultCertificate' => 'INSTALL_PATH/cacert.pem',
        'DefaultAction' => 'launch',
        'DefaultWarn' => '0',
        'DefaultUserActionRequired' => '0',
        'DefaultDeployNonnotified' => '1',
        'DefaultDeploySuccess' => '1',
        'DefaultDeployNotified' => '1',
        'DefaultDeployError' => '1',
        'DefaultMergeUserdefined' => '1',
        'DefaultMergeGroups' => '1',
        'DefaultDeployGroups' => '1',
        'DefaultDeleteInterfaces' => '1',
        // Default for PackagePath is provided by get()
        'LockValidity' => 600,
        'TraceDeleted' => false,
        'GroupCacheExpirationInterval' => 43200,
        'GroupCacheExpirationFuzz' => 43200,
        'CommunicationServerAddress' => 'localhost',
        'CommunicationServerPort' => 80,
        // Default for CommunicationServerUri is provided by get()
        // Defaults below this point are defined by communication server.
        'UseGroups' => 1,
        'SetGroupPackageStatus' => 1,
        'InspectRegistry' => 1,
        'AgentDeployment' => 0,
        'AgentUpdate' => 0,
        'PackageDeployment' => 0,
        'ScannersPerSubnet' => 2,
        'ScanSnmp' => 1,
        'ScannerMinDays' => 1,
        'ScannerMaxDays' => 14,
        'ScanAlways' => 0,
        'ScanningConfigurationInGroups' => 1,
        'ScanArpDelay' => 100,
        'SaveRawData' => 0,
        'SaveDir' => '/tmp',
        'SaveFormat' => 'OCS',
        'SaveOverwrite' => 0,
        'TrustedNetworksOnly' => 0,
        'InventoryFilter' => 0,
        'LimitInventory' => 0,
        'LimitInventoryInterval' => 300,
        'CustomProcessing' => 0,
        'SessionValidity' => 3600,
        'SessionCleanupInterval' => 86400,
        'SessionRequired' => 0,
        'LogPath' => '/var/log/ocsinventory-server',
        'LogLevel' => 0,
        'AutoDuplicateCriteria' => 15,
        'UpdateChangedSectionsOnly' => 1,
        'UpdateChangedSnmpSectionsOnly' => 1,
        'UseDifferentialUpdate' => 1,
        'UseTransactions' => 1,
        'UseCacheTables' => 0,
        'KeepObsoleteCacheItems' => 1,
        'CacheTableExpirationinterval' => 7,
        'AcceptNonZlib' => 0,
        'ContactInterval' => 12,
        'InventoryInterval' => 0,
        'DownloadPeriodDelay' => 60,
        'DownloadCycleDelay' => 60,
        'DownloadFragmentDelay' => 60,
        'DownloadMaxPriority' => 10,
        'DownloadTimeout' => 7,
    );

    /**
     * Options stored in the 'ivalue' column (everything else goes to 'tvalue')
     * @var array
     */
    protected static $_iValues = array(
        'LockValidity',
        'TraceDeleted',
        'GroupCacheExpirationInterval',
        'GroupCacheExpirationFuzz',
        'CommunicationServerPort',
        'UseGroups',
        'SetGroupPackageStatus',
        'InspectRegistry',
        'ContactInterval',
        'InventoryInterval',
        'AgentDeployment',
        'AgentUpdate',
        'PackageDeployment',
        'DownloadPeriodDelay',
        'DownloadCycleDelay',
        'DownloadFragmentDelay',
        'DownloadMaxPriority',
        'DownloadTimeout',
        'ScannersPerSubnet',
        'ScanSnmp',
        'ScannerMinDays',
        'ScannerMaxDays',
        'ScanAlways',
        'ScanningConfigurationInGroups',
        'ScanArpDelay',
        'SaveRawData',
        'SaveOverwrite',
        'TrustedNetworksOnly',
        'InventoryFilter',
        'LimitInventory',
        'LimitInventoryInterval',
        'CustomProcessing',
        'SessionValidity',
        'SessionCleanupInterval',
        'SessionRequired',
        'LogLevel',
        'AutoDuplicateCriteria',
        'UpdateChangedSectionsOnly',
        'UpdateChangedSnmpSectionsOnly',
        'UseDifferentialUpdate',
        'UseTransactions',
        'UseCacheTables',
        'KeepObsoleteCacheItems',
        'CacheTableExpirationinterval',
        'AcceptNonZlib',
    );

    /**
     * Option cache
     *
     * This is managed by get() and set().
     * @var array
     **/
    private static $_cache = array();

    /**
     * Return internal name matching the given logical name
     * @param string $option logical option name
     * @return string internal option name
     */
    static function getOcsOptionName($option)
    {
        if (isset(self::$optionMap[$option])) {
            return self::$optionMap[$option];
        } else {
            throw new UnexpectedValueException(
                'Unknown option: ' . $option
            );
        }
    }

    /**
     * Retrieve the value for a given option
     *
     * @param string $option Logical option name
     * @return mixed Option value (if set), default value (if defined) or NULL
     */
    static function get($option)
    {
        if (isset(self::$_cache[$option])) {
            return self::$_cache[$option];
        }

        $db = Model_Database::getAdapter();

        if (in_array($option, self::$_iValues)) {
            $column = 'ivalue';
        } else {
            $column = 'tvalue';
        }

        $value = $db->fetchOne(
            "SELECT $column FROM config WHERE name=?",
            self::getOcsOptionName($option)
        );

        if ($option == 'PackagePath') {
            if (!$value) {
                // Default can only be applied at runtime, not in static declaration
                $value = rtrim($_SERVER['DOCUMENT_ROOT'], DIRECTORY_SEPARATOR);
            }
            // Only base directory is stored in config. Always append real directory.
            $value .= '/download/';
        } elseif ($option == 'CommunicationServerUri') {
            if (!$value) {
                // Default can only be applied at runtime, not in static declaration
                $value = 'http://' .
                         self::get('CommunicationServerAddress') .
                         ':' .
                         self::get('CommunicationServerPort') .
                         '/ocsinventory';
            }
        }

        if ($value === false) {
            // No entry present in database
            if (isset(self::$_defaults[$option])) {
                // Apply default
                $value = self::$_defaults[$option];
            } else {
                // No default
                $value = null;
            }
        }

        self::$_cache[$option] = $value;
        return $value;
    }

    /**
     * Set the value for a given option
     *
     * By default, the value is stored in the database and in a cache. This can
     * be changed to store the value only in the cache for the lifetime of the
     * script, temporarily overriding the configured value. get() will always
     * prefer the cached value.
     *
     * @param string $option Logical option name
     * @param mixed $value Option value
     * @param bool $permanent Store value in database, not only in cache. Default: true.
     */
    static function set($option, $value, $permanent=true)
    {
        if (in_array($option, self::$_iValues)) {
            // Validate and cast $value
            if ($value === false or preg_match('/^-?[0-9]+$/', (string) $value)) {
                $value = (integer) $value;
            } else {
                throw new UnexpectedValueException(
                    'Tried to set non-integer value to integer option'
                );
            }
            $column = 'ivalue';
        } else {
            $value = (string) $value;
            $column = 'tvalue';
        }

        if ($option == 'PackagePath') {
            // Canonicalize path and strip /download path component. Don't use
            // realpath() for this to maintain the path as supplied, supporting
            // symbolic links.
            $path = preg_split('#[/\\\\]#', $value);
            do {
                $component = array_pop($path);
            } while ($component == ''); // skip trailing slashes
            if ($component != 'download') {
                throw new UnexpectedValueException('Path must end with \'/download\'');
            }
            $value = implode(DIRECTORY_SEPARATOR, $path);

        }

        if ($permanent) {
            $db = Model_Database::getAdapter();
            $ocsOptionName = self::getOcsOptionName($option);
            $oldValue = $db->fetchOne(
                "SELECT $column FROM config WHERE name=?",
                $ocsOptionName
            );
            if ($oldValue === false) { // No row yet
                $db->insert(
                    'config',
                    array(
                        'name' => $ocsOptionName,
                        $column => $value
                    )
                );
            } elseif ($value != $oldValue) { // Update row only if necessary
                $db->update(
                    'config',
                    array($column => $value),
                    array('name=?' => $ocsOptionName)
                );
            }
        }

        self::$_cache[$option] = $value;
    }

}
