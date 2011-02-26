====================================================================
$Id$

Copying and distribution of this file, with or without modification,
are permitted in any medium without royalty provided the copyright
notice and this notice are preserved. This file is offered as-is,
without any warranty.
====================================================================


Quick start to get Braintacle running:

1. If you don't already have a working installation of OCS Inventory NG, install it -
   either the original version from http://www.ocsinventory-ng.org/ or the patched
   version that comes bundled with Braintacle in the ocsinventory directory. See the
   README.Braintacle.html file in the same directory for details.
   This version is recommended.

2. Enable mod_rewrite and mod_env in your Apache configuration.

3. Set "AllowEncodedSlashes On" on the virtual host that runs Braintacle.

4. Copy doc/braintacle.conf to a place where Apache will read it. Typically this is
   "/etc/apache2/conf.d". Edit this file to suit your needs.

5. Have Apache reload its configuration.

6. Copy doc/database.ini to application/configs and adjust the settings.
   The sample configuration is set to OCSinventory's defaults.
   This file should not be world-readable, only for your webserver.

7. Braintacle requires MD5-hashed passwords. This is the default when creating an
   account or changing the password with OCSinventory console. If you are still
   using the default admin account (which comes with a cleartext password), you
   have to change the password to get an MD5 password.

8. Point your browser to Braintacle's URL. Log in with an OCSinventory administrator
   account and have fun.


More information can be found in the doc/ directory.


====================================================================

LICENSES

Braintacle is released under the GNU General Public License v2 or later.
You can find the full license in the COPYING file in the same directory that
contains this file.

This project contains some third party code:

- A heavily patched version of OCS inventory NG (http://www.ocsinventory-ng.org/,
  licensed under the GNU General Public License v2) is included in the ocsinventory/
  directory.
  See http://www.ocsinventory-ng.org/index.php?page=license

- A copy of Zend Framework (http://framework.zend.com, licensed under the
  New BSD License) is included in the library/Zend/ directory.
  See http://framework.zend.com/license

- The library/PEAR/ directory contains a copy of some PEAR packages, partially
  patched. See the Readme.txt in the same directory for details.
  They are licensed under the BSD license, see
  http://www.opensource.org/licenses/bsd-license.php

  - MDB2_Schema (http://pear.php.net/package/MDB2_Schema)
  - MDB2_Driver_oci8 (http://pear.php.net/package/MDB2_Driver_oci8)
  - MDB2, patched (http://pear.php.net/package/MDB2/)
  - MDB2_Driver_pgsql, patched (http://pear.php.net/package/MDB2_Driver_pgsql)
  - MDB2_Driver_mysql, patched (http://pear.php.net/package/MDB2_Driver_mysql).

- The file application/configs/macaddresses-vendors.txt is taken from the
  wireshark project (http://wireshark.org) under a different name. GPLv2
  and copyright information are contained at the top of this file.
