<?php
/**
 * MDB2-MySQL-wrapper
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
 */

error_reporting (E_ALL); // to reveal migration issues (and far more bugs in the original code...)

// The (depreceated) magic_quotes_* feature may interfere with this code.
if (ini_get ('magic_quotes_gpc'))
	die ('Please disable magic_quotes_gpc in your php.ini and/or Apache configuration.');
if (ini_get ('magic_quotes_runtime'))
	die ('Please disable magic_quotes_runtime in your php.ini and/or Apache configuration.');

// search includes in the PEAR directory first, but behind current directory (if defined)
if (substr (get_include_path(), 0, 2) == '.' . PATH_SEPARATOR) {
    set_include_path ('.' . PATH_SEPARATOR . realpath (dirname (__FILE__) . '/../../../library/PEAR') . substr (get_include_path(), 1));
} else {
    set_include_path (realpath (dirname (__FILE__) . '/../../../library/PEAR') . PATH_SEPARATOR . get_include_path());
} 

require_once ('MDB2.php');


// last opened connection; used as default by certain functions.
$mdb2_lastConnection = NULL;

// Number of rows affected by last query;
$mdb2_affectedRows = 0;

// keep track of errors for mdb2_error() and mdb2_errno()
$mdb2_last_error = "";
$mdb2_last_errno = MDB2_OK;

// Error handler: Set error variables and log the details.
// Applies automatically to all PEAR functions.
function pear_errorCallback ($err) {
	global $mdb2_last_error, $mdb2_last_errno;

	$mdb2_last_error = $err->getMessage();
	$mdb2_last_errno = $err->getCode();

	error_log ('ocsinventory: ' . $err->getMessage(), 0);
	error_log ('ocsinventory: ' . $err->getUserInfo(), 0);
	foreach (debug_backtrace() as $backtrace) {
		if ($backtrace["function"] != "pear_errorCallback" and
		    $backtrace["function"] != "call_user_func" and
		    $backtrace["function"] != "PEAR_Error" and
		    $backtrace["function"] != "MDB2_Error" and
		    $backtrace["function"] != "raiseError" and
		    $backtrace["function"] != "prepare" and
		    $backtrace["function"] != "execute" and
		    $backtrace["function"] != "_execute")
			error_log ("function: $backtrace[function]  file: $backtrace[file]  line: $backtrace[line]");
	}
}
PEAR::setErrorHandling (PEAR_ERROR_CALLBACK, 'pear_errorCallback');

// Used internally to reset error variables
function reset_errors() {
	global $mdb2_last_error, $mdb2_last_errno;

	$mdb2_last_error = "";
	$mdb2_last_errno = MDB2_OK;
}


// This Function is used internally by some wrapper functions to determine the connection to be used.
// If $obj is non-null, it is returned, otherwise the last connection opened is returned.
// Unlike the original mysql functions, no connection is established automatically
// and a fatal error is raised if this is still NULL.
function mdb2_getConnection ($obj=NULL)
{
	if (is_null ($obj)) {
		global $mdb2_lastConnection;
		if (is_null ($mdb2_lastConnection)) {
			PEAR::raiseError ('ERROR: No database connection has been previously established.');
			die();
		}
		$obj =& $mdb2_lastConnection;
	}
	return $obj;
}

// Replacement for mysql_connect. Arguments are not strictly compatible.
// You have to change the calling code!
function mdb2_connect ( $driver, $server, $username, $password, $database, $charset=NULL)
{
	reset_errors();

	$dsn = array(
		'phptype'  => $driver,
		'username' => $username,
		'password' => $password
	);
	if ($charset)
		$dsn['charset'] = $charset;
	if (substr ($server,0,1) == '/') {
		$dsn['protocol'] = 'unix';
		$dsn['socket'] = $server;
	} else {
		$dsn['protocol'] = 'tcp';
		$dsn['hostspec'] = $server;
	}
	$dsn['database'] = $database;
	
	$mdb2_options = array (
		'portability' => MDB2_PORTABILITY_ALL ^ MDB2_PORTABILITY_EMPTY_TO_NULL, // avoid possible side-effects
		'quote_identifier' => true,
		'result_buffering' => true, // required for numRows() to work
		'field_case' => CASE_LOWER
	);
        if ($driver == 'mysql')
                $mdb2_options['use_transactions'] = false;

	$mdb2 =& MDB2::connect($dsn, $mdb2_options);
	if (PEAR::isError($mdb2)) {
		return false;
	} else {
		global $mdb2_lastConnection;
		$mdb2_lastConnection = $mdb2;
		return $mdb2;
	}
}

// Replacement for mysql_error(). Returned string is safe for insertion into HTML.
function mdb2_error($obj = NULL) {
	global $mdb2_last_error;
	return $mdb2_last_error;
}

// Replacement for mysql_errno()
// NOTE: This function returns MDB2 error numbers instead of native numbers. Adjust calling code!
function mdb2_errno($obj = NULL) {
	global $mdb2_last_errno;
	return $mdb2_last_errno;
}

// Replacement for mysql_query()
// The optional $types and $args arguments are an extension that allow "?" placeholders inside the SQL string.
// If given, $types contains a single string or an array with MDB2 datatypes of the arguments (default: all arguments are treated as strings)
// and $args contains a single argument or array of arguments to be passed to the statement (default: no arguments)
// The use of placeholders is strongly recommended for untrusted input because there's no need to worry about
// proper quoting and escaping. This makes the code much safer and more readable.
function mdb2_query ($query, $mdb2 = NULL, $types=NULL, $args=NULL)
{
	reset_errors();

	global $mdb2_affectedRows;
	$mdb2 =& mdb2_getConnection ($mdb2);

	// extract command from query string
	$query = ltrim($query);
	$pos = strpos($query,' ');
	if ($pos === false) {
		$command = $query;
	} else {
		$command = substr ($query, 0, $pos);
	}
	switch (strtoupper($command))
	{
		case 'SELECT':
			$query_type = MDB2_PREPARE_RESULT;
			break;
		case 'INSERT':
		case 'UPDATE':
		case 'DELETE':
			$query_type = MDB2_PREPARE_MANIP;
			break;
		default:
			$mdb2->raiseError();
			die ('Unsupported SQL command: ' . $command);
	}
	if (!is_null ($types) and !is_array ($types)) { // prepare() requires an array, even for a single value
		$types = array ($types);
	}
	$sth = $mdb2->prepare ($query, $types, $query_type);
	if (PEAR::isError ($sth)) {
		if ($query_type === MDB2_PREPARE_MANIP)
			$mdb2_affectedRows = -1;
		return false;
	}

	$res =& $sth->execute ($args);
	if (PEAR::isError ($res)) {
		if ($query_type === MDB2_PREPARE_MANIP)
			$mdb2_affectedRows = -1;
		$res = false;
	}

	if ($query_type === MDB2_PREPARE_MANIP) {
		$mdb2_affectedRows = $res;
		$res = true;
	}

	$sth->free();
	return $res;
}

// replacement for mysql_fetch_row()
function mdb2_fetch_row (&$result)
{
	reset_errors();

	$ret = $result->fetchRow (MDB2_FETCHMODE_ORDERED);
	return (is_null ($ret)) ? false : $ret;
}

// replacement for mysql_fetch_assoc()
// We provide an extra option $case which defines whether the returned keys are uppercase or lowercase. Default: lowercase.
function mdb2_fetch_assoc (&$result, $case=CASE_LOWER)
{
	reset_errors();

	$result->db->setOption ('field_case', $case);
	$ret = $result->fetchRow (MDB2_FETCHMODE_ASSOC);
	$result->db->setOption ('field_case', CASE_LOWER);
	return (is_null ($ret)) ? false : $ret;
}

// replacement for mysql_fetch_object()
// The optional arguments are not recognized because their functionality is not provided by the MDB2 class.
// Instead, we provide an extra option $case which defines whether the members are uppercase or lowercase. Default: lowercase.
function mdb2_fetch_object (&$result, $case=CASE_LOWER)
{
	reset_errors();

	$result->db->setOption ('field_case', $case);
	$ret = $result->fetchRow (MDB2_FETCHMODE_OBJECT);
	$result->db->setOption ('field_case', CASE_LOWER);
	return (is_null ($ret)) ? false : $ret;
}

// replacement for mysql_num_rows()
function mdb2_num_rows(&$result)
{
	reset_errors();

	return $result->numRows();
}

// replacement for mysql_affected_rows()
// The optional argument is not recognized because the affected rows of different connections are difficult to keep track of with the MDB2 classes.
function mdb2_affected_rows()
{
	reset_errors();

	global $mdb2_affectedRows;
	return $mdb2_affectedRows;
}

// replacement for mysql_real_escape_string()
function mdb2_real_escape_string ($str, $mdb2 = NULL)
{
	reset_errors();

	$mdb2 =& mdb2_getConnection ($mdb2);
	return $mdb2->escape($str, false);
}

// This function has no MySQL counterpart. It is used to safely quote any column identifier.
// Since quoting makes PostgreSQL treat it case senitive, it is converted to lowercase first.
function mdb2_quote_identifier ($str, $mdb2 = NULL, $escape_delimiters = false)
{
	$mdb2 =& mdb2_getConnection ($mdb2);
	if ($escape_delimiters)
		return strtr ($mdb2->quoteIdentifier (strtolower (strtr ($str, ".", "#"))), "#", ".");
	else
		return $mdb2->quoteIdentifier (strtolower ($str));
}

// reverse MDB2 quoteIdentifier() method
function mdb2_unquote_identifier ($str, $mdb2 = NULL)
{
	$mdb2 =& mdb2_getConnection ($mdb2);
	$quoteStart = $mdb2->identifier_quoting["start"];
	$quoteEnd = $mdb2->identifier_quoting["end"];
	$quoteEscape = $mdb2->identifier_quoting["escape"];

	$parts = explode (".", $str);
	foreach (array_keys($parts) as $k) {
		$extracted = array();
		if (ereg (quotemeta ($quoteStart) . "(.*)" . quotemeta ($quoteEnd), $parts[$k], $extracted)) {
			$parts[$k] = str_replace($quoteEscape.$quoteEnd, $quoteEnd, $extracted[1]); // the content without surrounding quotes and unescaped
		}
	}
	return implode (".", $parts);
}
?>
