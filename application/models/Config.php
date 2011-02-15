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
 * instead through {@link getOption()}.
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
        // Defaults for merging duplicates
        'DefaultMergeUserdefined' => 'BRAINTACLE_DEFAULT_MERGE_USERDEFINED',
        'DefaultMergePackages' => 'BRAINTACLE_DEFAULT_MERGE_PACKAGES',
        // Should blacklisted software be displayed in computer and software inventory?
        'DisplayBlacklistedSoftware' => 'BRAINTACLE_DISPLAY_BLACKLISTED_SOFTWARE',
        // OCS options. The list is incomplete; only options used by Braintacle are listed.
        'PackagePath' => 'DOWNLOAD_PACK_DIR', // getOption() will append '/download/'
        'LockValidity' => 'LOCK_REUSE_TIME', // Seconds before a computer's lock expires
        'TraceDeleted' => 'TRACE_DELETED', // Use deleted_equiv table
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
        // Default for PackagePath is provided by getOption()
        'LockValidity' => 600,
        'TraceDeleted' => false,
    );

    /**
     * Return internal name matching the given logical name
     * @param string $option logical option name
     * @return string internal option name
     */
    static function getOcsOptionName($option)
    {
        return Model_Config::$optionMap[$option];
    }

    /**
     * Retrieve the value for a given option
     *
     * The config table stores 2 values in each row: ivalue (integer) and
     * tvalue (text). Only one of them is used; the other one is usually NULL.
     * There are a few rows where tvalue is relevant and ivalue is 0, in which
     * case ivalue is ignored. This is an unnecessary complication since the
     * datatype is ignored by the application. This function performs following
     * logic to determine the result:
     *
     * 1. If the option is present in the table and its tvalue is not null,
     *    tvalue is returned, otherwise ivalue is returned.
     * 2. If the option is not present in the table and a default is defined in
     *    $_defaults, the default is returned, otherwise NULL is returned.
     * @param string $option Logical option name
     * @return string Option value
     */
    static function getOption($option)
    {
        $db = Zend_Registry::get('db');

        $row = $db->fetchRow(
            'SELECT ivalue,tvalue FROM config WHERE name=?',
            Model_Config::getOcsOptionName($option)
        );
        if ($row) {
            if (!is_null($row->tvalue)) {
                $value = $row->tvalue;
            } else {
                $value = $row->ivalue;
            }
        } else {
            if (array_key_exists($option, Model_Config::$_defaults)) {
                $value = Model_Config::$_defaults[$option];
            } else {
                $value = null;
            }
        }

        if ($option == 'PackagePath') {
            if (!$value) {
                $value = $_SERVER['DOCUMENT_ROOT'];
            }
            $value .= '/download/';
        }

        return $value;
    }

}
