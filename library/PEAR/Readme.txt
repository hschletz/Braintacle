====================================================================
$Id$

Copying and distribution of this file, with or without modification,
are permitted in any medium without royalty provided the copyright
notice and this notice are preserved. This file is offered as-is,
without any warranty.
====================================================================


This directory contains patched and beta versions of some PEAR classes. This
allows you to keep the stable versions in your global include path. Fiddling
with betas there can be tricky.
They will eventually be removed from this distribution with the next stable
release if all bugs are fixed then.

Included classes:

- MDB2_Schema 0.8.4
- SVN snapshot (Rev. 303766) of
  - MDB2 + patch for PEAR bug #16280
  - MDB2_driver_pgsql
  - MDB2_driver_mysql + patches for PEAR bugs #16557,#18057
  - MDB2_driver_oci8

Note that additional dependencies are not included here. These are best
installed through the 'pear' command (available in the php-pear package on
Debian/Ubuntu/Fedora/RedHat or php5-pear on SUSE):

# pear install <Package>

If this is not possible for some reason, you must install the packages manually:

1. download the package from http://pear.php.net/package/<Package>/download
2. unpack it to a temporary path
3. copy the content of the <Package>-<Version> directory (not the directory
itself) to a place inside your include path (like this directory). The subdirs
"docs" and "tests" and the package.xml file are not needed.
