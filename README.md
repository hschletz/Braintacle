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

- A [PostgreSQL](http://postgresql.org) or [MySQL](http://mysql.org) database.
  Support for other database backends may be added in the future.

- [Apache httpd](http://httpd.apache.org) web server with
  [mod_perl](http://perl.apache.org) and a database-specific DBD module for the
  main server component.

- A web server with [PHP](http://php.net) 5.5 or later for the administration
  console. PHP is also required for most of the command line tools. The following
  PHP extensions are required:

  - A database-specific PHP extension,
  <http://framework.zend.com/manual/2.4/en/modules/zend.db.adapter.html>

  - The "intl" extension

  - The "zip" extension is optional. If present, the package builder can create ZIP
    archives on the fly.

- [Composer](https://getcomposer.org/) is recommended for setup of dependencies.
  See [INSTALL.md](INSTALL.md) for additional requirements if you don't install
  via Composer.

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

- The file module/Library/data/MacAddress/manuf is taken from the
  [Wireshark](http://wireshark.org) project. GPLv2 and copyright information are
  contained at the top of this file.
