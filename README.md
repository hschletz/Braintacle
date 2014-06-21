<!--
Copying and distribution of this file, with or without modification,
are permitted in any medium without royalty provided the copyright
notice and this notice are preserved. This file is offered as-is,
without any warranty.
-->

About Braintacle
----------------

Braintacle is a set of applications for managing hard- and software on a
network. Braintacle keeps a semi-automatic inventory of computers, other
network-connected devices and installed software, and provides a generic
infrastructure for downloading files and executing commands on selected clients,
allowing centralized software updates, remote configuration and more. For
developers, a PHP API is provided to access the inventory and control the
applications.

Braintacle currently reuses some code from [OCS Inventory
NG](http://ocsinventory-ng.org). That code is enhanced with extra features,
easier maintenance and improved stability. The administration console is written
from scratch. It is also possible to install Braintacle along an existing OCS
Inventory NG installation and share the database, but this is only recommended
for testing purposes. Not all of Braintacle's features will be available, and
compatibility may be dropped in the future.


Requirements
------------

- A [PostgreSQL](http://postgresql.org) or [MySQL](http://mysql.org) database.
  Support for other database backends may be added in the future.

- [Apache httpd](http://httpd.apache.org) web server with
  [mod_perl](http://perl.apache.org) and a database-specific DBD module for the
  main server component.

- A web server with [PHP](http://php.net) 5.3.3 or later for the administration
  console. PHP is also required for most of the command line tools. The following
  PHP extensions are required:

  - A database-specific PHP extension,
  <http://framework.zend.com/manual/2.2/en/modules/zend.db.adapter.html>

  - The "intl" extension

  There are also some PHP libraries required in the include path:

  - [Zend Framework](http://framework.zend.com) 2.x (tested with 2.2)

  - [NADA](https://github.com/hschletz/NADA)

- On every client that should be managed through Braintacle, either the
  [OCS Inventory NG agent](http://www.ocsinventory-ng.org/en/download/download-agent.html) or
  [FusionInventory Agent](http://www.fusioninventory.org/documentation/agent/installation/)
  must be installed.


Installation
------------

Refer to the [INSTALL.md](INSTALL.md) file in the same directory that contains
this file.


Further reading
---------------

More detailed information is available in the [doc/](doc) directory.


--------
LICENSES
--------

Braintacle is released under the GNU General Public License v2 or later. You can
find the full license in the [COPYING](COPYING) file in the same directory that
contains this file.

This project contains some third party code:

- A patched version of the [OCS inventory NG server
  components](http://www.ocsinventory-ng.org/), licensed under the GNU General
  Public License v2) is included in the ocsinventory/ directory.
  See <http://www.ocsinventory-ng.org/en/about/licence.html>.

- The library/PEAR/ directory contains a copy of some PEAR packages, partially
  patched. See the Readme.txt in the same directory for details.
  They are licensed under the BSD license, see
  <http://www.opensource.org/licenses/bsd-license.php>

  - [PEAR](http://pear.php.net/package/PEAR)
  - [MDB2](http://pear.php.net/package/MDB2)
  - [MDB2_Driver_pgsql](http://pear.php.net/package/MDB2_Driver_pgsql)
  - [MDB2_Driver_mysql](http://pear.php.net/package/MDB2_Driver_mysql)
  - [MDB2_Driver_oci8](http://pear.php.net/package/MDB2_Driver_oci8)
  - [MDB2_Schema](http://pear.php.net/package/MDB2_Schema)
  - [XML_Parser](http://pear.php.net/package/XML_Parser)

- The file module/data/MacAddress/manuf is taken from the
  [Wireshark](http://wireshark.org) project. GPLv2 and copyright information are
  contained at the top of this file.
