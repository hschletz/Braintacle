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

- A web server with [PHP](https://php.net) 7.3 or later for the administration
  console. PHP is also required for most of the command line tools. The following
  PHP extensions are required:

  - A database-specific PHP extension,
  <https://docs.zendframework.com/zend-db/adapter/>

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

Refer to the INSTALL.md in the downloaded archive.

License
-------

Braintacle is released under the [GNU General Public License v2](http://www.gnu.org/licenses/old-licenses/gpl-2.0.html) or later.

For third party code, see README.md in the downloaded archive.
