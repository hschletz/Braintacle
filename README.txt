====================================================================
$Id$

Copying and distribution of this file, with or without modification,
are permitted in any medium without royalty provided the copyright
notice and this notice are preserved. This file is offered as-is,
without any warranty.
====================================================================


Quick start to get Braintacle running:

1.  Extract the downloaded tarball to a location outside the webserver's
    document root. The documentation and sample configuration assumes
    /usr/local/share/braintacle. IT IS VERY IMPORTANT THAT THE CONTENT OF THIS
    DIRECTORY IS NOT DIRECTLY VISIBLE TO THE BROWSER. It will contain sensitive
    data (config files with database credentials) which would otherwise leak to
    the outside world.

2.  Enable mod_rewrite and mod_env in your Apache configuration.

3.  Set "AllowEncodedSlashes On" on the virtual host that runs Braintacle.

4.  Create a copy of config/braintacle-console.conf.template and make it known
    to Apache, either by placing it in a directory where Apache will read it
    (typically /etc/apache2/conf.d or similar) or by including it in a
    particular VirtualHost definition. See the comments inside the file for
    details. Edit the file to suit your needs.

5.  Have Apache reload its configuration.

6.  Copy config/database.ini.template to config/database.ini and adjust the
    settings. This file should not be world-readable, only for your webserver.
    You could also copy it to /etc or /usr/local/etc and create a symbolic link
    in the config/ directory instead. This has the advantage of keeping /usr
    free of user-configurable data which does not actually belong there.

7.  If you don't already have a working installation of OCS Inventory NG, either
    install it or install the patched version that comes bundled with Braintacle
    in the ocsinventory directory. See the README.html file in the same
    directory for details. This version is recommended.

8.  Point your browser to Braintacle's URL. Log in with an OCSinventory
    administrator account (default username: 'admin', password 'admin') and have
    fun.

9.  It is strongly recommended to change the default password (via
    Preferences->Users).

10. If you want to use Braintacle to deploy packages, create a directory with
    write access for the web server. For compatibility with OCS Inventory NG,
    it must be named 'download'. Recommended is /var/lib/braintacle/download.
    Uploaded packages will be stored in this directory.
    In the Braintacle web console, enter the path unter Preferences->Packages.
    In the same dialog, you have to specify 2 URLs (1 for HTTP, 1 for HTTPS)
    which must point to this directory. Configure Apache for this purpose, like
    with the file config/braintacle-download.conf.template.

More information can be found in the doc/ directory and on the project site at:
http://savannah.nongnu.org/projects/braintacle


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

  - PEAR (http://pear.php.net/package/PEAR)
  - MDB2 (http://pear.php.net/package/MDB2/)
  - MDB2_Driver_pgsql (http://pear.php.net/package/MDB2_Driver_pgsql)
  - MDB2_Driver_mysql (http://pear.php.net/package/MDB2_Driver_mysql)
  - MDB2_Driver_oci8 (http://pear.php.net/package/MDB2_Driver_oci8)
  - MDB2_Schema (http://pear.php.net/package/MDB2_Schema)
  - XML_Parser (http://pear.php.net/package/XML_Parser/)

- The file application/configs/macaddresses-vendors.txt is taken from the
  wireshark project (http://wireshark.org) under a different name. GPLv2
  and copyright information are contained at the top of this file.

- A copy of NADA (http://savannah.nongnu.org/projects/nada, licensed under the
  BSD 2-Clause License) is included in the library/NADA/ directory. See
  http://svn.savannah.nongnu.org/svn/nada/trunk/LICENSE
