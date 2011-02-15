<?php
/**
 * Wrapper for MDB2::factory()
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
 * @package Library
 * @filesource
 */
/**
 Includes
 */
Braintacle_MDB2::setErrorReporting();
require_once 'MDB2.php';
Braintacle_MDB2::resetErrorReporting();
require_once 'Zend/Config/Ini.php';
/**
 * MDB2 connector
 *
 * This class implements a static {@link factory()} method that returns an
 * MDB2_Driver_Common object on success, just like MDB2::factory() would.
 * Braintacle_MDB2::factory() however integrates into the application:
 * - It does not take any arguments. The DSN and options are determined from
 *   the application's configuration and some hardcoded defaults that are
 *   appropriate for use within Braintacle.
 * - The returned object is set up to throw exceptions on error which get
 *   caught by the application's default exception handler unless caught
 *   manually.
 *
 *
 * WARNING: MDB2 generates a lot of E_STRICT and E_DEPRECATED messages.
 * It is therefore necessary to suppress these. {@link setErrorReporting()}
 * can be used for this. {@link resetErrorReporting()} will revert the error
 * reporting level to its previous state.
 * These methods can not be nested. You should encapsulate every MDB2 method
 * call (or entire code blocks if there are too many) inside these two methods
 * and revert to the previous level as soon as possible to prevent any E_STRICT
 * and E_DEPRECATED messages from staying unnoticed.
 * @package Library
 */
class Braintacle_MDB2
{

    /**
     * Error reporting level before last invocation of {@link setErrorReporting()}
     * @var integer
     */
    protected static $_oldLevel;

    /**
     * Return an MDB2_Driver_Common object that connects to the database
     * according to the database.ini file
     *
     * The global APPLICATION_PATH and APPLICATION_ENV constants must be set to
     * locate the file and determinedthe desired configuration.
     * This method should be called encapsulated within
     * {@link setErrorReporting()}/{@link resetErrorReporting()}.
     */
    static function factory()
    {
        // Get DSN information from application's database config.
        $config = new Zend_Config_Ini(
            APPLICATION_PATH . '/configs/database.ini',
            APPLICATION_ENV
        );
        // Map Zend DB adapter to MDB2 driver
        $adapter = $config->adapter;
        switch (strtolower($adapter))
        {
            case 'pdo_pgsql':
                $driver = 'pgsql';
                break;
            case 'mysqli':
            case 'pdo_mysql':
                $driver = 'mysql';
                break;
            case 'oracle':
            case 'pdo_oci':
                $driver = 'oci8';
                break;
            case 'sqlsrv':
            case 'pdo_mssql':
                $driver = 'mssql';
                break;
            default:
                throw new InvalidArgumentException(
                    "Cannot map Zend DB adapter '$adapter' to a known MDB2 driver."
                );
        }
        // Build DSN array.
        $dsn['phptype'] = $driver;
        $dsn['charset'] = 'utf8';
        $dsn['database'] = $config->params->dbname;
        $dsn['username'] = $config->params->username;
        $dsn['password'] = $config->params->password;
        $dsn['port'] = $config->params->port;
        $server = $config->params->host;
        if (substr($server, 0, 1) == '/') {
            $dsn['protocol'] = 'unix';
            $dsn['socket'] = $server;
        } else {
            $dsn['protocol'] = 'tcp';
            $dsn['hostspec'] = $server;
        }

        // Create MDB2 object.
        $options = array (
            'quote_identifier' => true,
            'portability' => MDB2_PORTABILITY_ALL ^ MDB2_PORTABILITY_EMPTY_TO_NULL,
            'result_buffering' => true, // required for numRows() to work
            'field_case' => CASE_LOWER
        );
        if ($dsn['phptype'] == 'mysql') {
            $options['use_transactions'] = false;
        }

        // Create MDB2 object and integrate it with the application's exception handling.
        $mdb2 = MDB2::factory($dsn, $options);
        if (PEAR::isError($mdb2)) {
            throw new PEAR_Exception('MDB2 connection failed.');
        }
        $mdb2->setErrorHandling(PEAR_ERROR_EXCEPTION);

        return $mdb2;
    }

    /**
     * Suppress PHP warnings generated by MDB2.
     * This should be followed by {@link resetErrorReporting()} as soon as
     * possible.
     * @return integer Previous error_reporting value, useful for restoring
     *         manually if this is nested (not recommended though)
     */
    static function setErrorReporting()
    {
        $oldLevel = ini_get('error_reporting');
        $newLevel = $oldLevel & ~E_STRICT;
        if (version_compare(PHP_VERSION, '5.3.0', '>=')) {
            $newLevel &= ~E_DEPRECATED;
        }
        self::$_oldLevel = $oldLevel;
        return error_reporting($newLevel);
    }

    /**
     * Reset error_reporting level to its state before setErrorReporting() invocation
     */
    static function resetErrorReporting()
    {
        $oldLevel = self::$_oldLevel;
        if (!is_null($oldLevel)) {
            error_reporting($oldLevel);
        }
    }

}
