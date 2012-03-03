<?php
/*
 * Configuration file for ocsreports
 *
 * $Id$
 *
 * Copying and distribution of this file, with or without modification,
 * are permitted in any medium without royalty provided the copyright
 * notice and this notice are preserved. This file is offered as-is,
 * without any warranty.
 */

// The first configuration block is set to OCS Inventory's defaults.

// MDB2 driver to use, set to 'mysql' or 'pgsql'.
$_SESSION['MDB2_DRIVER'] = 'mysql';
// Database server
$_SESSION['SERVEUR_SQL'] = 'localhost';
// Database user
$_SESSION['COMPTE_BASE'] = 'ocs';
// Password for database user
$_SESSION['PSWD_BASE'] = 'ocs';

// Alternative configuration for PostgreSQL via local socket
// $_SESSION['MDB2_DRIVER'] = 'pgsql';
// $_SESSION['SERVEUR_SQL'] = '/var/run/postgresql';
// $_SESSION['COMPTE_BASE'] = 'ocs';
// $_SESSION['PSWD_BASE'] = 'ocs';
