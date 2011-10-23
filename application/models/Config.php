<?php
/**
 * Class for retrieving and setting options from the config table
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
/** Class for retrieving and setting options from the config table
 * 
 * All access to the config table should happen through this class via its
 * accessor methods. {@link $optionMap} defines mappings from logical option
 * names to the names actually stored in the table. These internal names are
 * sometimes not very meaningful. Only the logical names should be used
 * instead through {@link get()}.
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
        'LockValidity' => 'LOCK_REUSE_TIME', // Seconds before a computer's lock expires
        'TraceDeleted' => 'TRACE_DELETED', // Use deleted_equiv table
        'GroupCacheExpirationInterval' => 'GROUPS_CACHE_REVALIDATE', // Seconds between cache rebuilds
        'GroupCacheExpirationFuzz' => 'GROUPS_CACHE_OFFSET', // Random range added to interval
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
        'GroupCacheExpirationInterval' => 600,
        'GroupCacheExpirationFuzz' => 600,
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
    );

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
        $db = Zend_Registry::get('db');

        if (in_array($option, self::$_iValues)) {
            $column = 'ivalue';
        } else {
            $column = 'tvalue';
        }

        $value = $db->fetchOne(
            "SELECT $column FROM config WHERE name=?",
            Model_Config::getOcsOptionName($option)
        );

        if ($option == 'PackagePath') {
            if (!$value) {
                // Default can only be applied at runtime, not in static declaration
                $value = $_SERVER['DOCUMENT_ROOT'];
            }
            // Only base directory is stored in config. Always append real directory.
            $value .= '/download/';
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

        return $value;
    }

    /**
     * Set the value for a given option
     *
     * @param string $option Logical option name
     * @param mixed $value Option value
     */
    static function set($option, $value)
    {
        $db = Zend_Registry::get('db');

        if (in_array($option, self::$_iValues)) {
            // Validate and cast $value
            if ($value === false or ctype_digit((string) $value)) {
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

}
