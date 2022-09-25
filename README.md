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


Requirements
------------

- A [PostgreSQL](https://postgresql.org) or [MySQL](https://www.mysql.com) (or one of its derivates, like [MariaDB](https://mariadb.org)) database.
  Support for other database backends may be added in the future.

- [Apache httpd](http://httpd.apache.org) with [mod_perl](https://perl.apache.org) and a database-specific DBD module for the
  main server component.

- A web server with [PHP](https://php.net) 7.4 or later for the administration
  console. PHP is also required for most of the command line tools. The following
  PHP extensions are required:

  - A database-specific PHP extension, see
    https://docs.laminas.dev/laminas-db/adapter/#creating-an-adapter-using-configuration

  - The "intl" extension

  - The "mbstring" extension

  - The "zip" extension is optional. If present, the package builder can create ZIP
    archives on the fly.

  - The "gmp" extension is required on 32 bit PHP installations only.

- [Composer](https://getcomposer.org/)

- On every client that should be managed through Braintacle, either the
  [OCS Inventory NG agent](https://github.com/OCSInventory-NG/) for Windows/UNIX/Android or
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
  components](https://www.ocsinventory-ng.org/), licensed under the GNU General
  Public License v2) is included in the server/ directory.

- The file module/Library/data/MacAddress/manuf is taken from the
  [Wireshark](https://wireshark.org) project. GPLv2 and copyright information are
  contained at the top of this file.
