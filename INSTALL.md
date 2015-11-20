<!--
Copying and distribution of this file, with or without modification,
are permitted in any medium without royalty provided the copyright
notice and this notice are preserved. This file is offered as-is,
without any warranty.
-->

Installing Braintacle
=====================

Braintacle consists mostly of a database and 2 web applications. Since you may
already have a database and web server running and configuration may differ
significantly from site to site, there is no automated installation routine.
Instead, these are generic instructions which should fit the most common setups.
Depending on your particular site configuration, the installation steps may
require some tweaking.

Templates for all necessary configuration files are provided. These are tuned 
towards the following setup:

- A PostgreSQL database

- Apache httpd is used for both the server and the console

- Braintacle is installed to /usr/local/share/braintacle

All these options (except for the webserver - Apache httpd is required for the
server, but not the console) can be changed, with the requirement of additional
tweaking. The above setup is recommended because this has been well tested and
installation is easier.


Download and extract the files
------------------------------

Download Braintacle from <https://github.com/hschletz/braintacle>, either as a
ZIP archive or via git or svn. The master branch is usually stable, but there
are also tagged releases available.

Extract the files to a location outside the webserver's document root. **IT IS
VERY IMPORTANT THAT BRAINTACLE'S ROOT DIRECTORY IS NOT DIRECTLY VISIBLE TO THE
BROWSER.** It will contain sensitive data (config files with database
credentials) which would otherwise leak to the outside world. For example, if
your web server is configured to serve files from /srv/www/htdocs/, do **not**
place the Braintacle directory there. The recommended location is
/usr/local/share/braintacle/, which is assumed for the rest of the
documentation, and also used by the sample configuration.


Set up libraries and PHP environment
------------------------------------

If not already present on your system, download and extract [Zend Framework
2.4.8 or later](http://framework.zend.com/downloads/latest#ZF2)
and [NADA](https://github.com/hschletz/NADA/archive/master.zip) to an arbitrary
location (for example, /usr/local/share/php).

These libraries don't need extra setup, but must reside within PHP's include
path, which can be set the usual way. If you don't want to mess with php.ini
globally, you can set the path in the Apache configuration per application (as
proposed in the config template, see below). For the command line tools, the
path can be given via the `-d` option:

    php -d include_path=/usr/local/share/php/Zend/library:/usr/local/share/php/NADA ...


Set up the database
-------------------

Install a PostgreSQL or MySQL server, if not already available, and log into
that database server with superuser privileges (typically the 'postgres' user
for PostgreSQL, 'root' for MySQL). Create a new database user (if not already
present) and the database with access privileges for that user. The database
must use Unicode.

For PostgreSQL, run:

    CREATE USER username WITH PASSWORD 'passwd';
    CREATE DATABASE braintacle OWNER username ENCODING 'UTF8';

For MySQL, run:

    CREATE USER username IDENTIFIED BY 'passwd';
    CREATE DATABASE braintacle CHARACTER SET utf8;
    GRANT ALL ON braintacle.* TO username;

You can choose any database name, user name and password.

Copy or rename the file /usr/local/share/braintacle/config/braintacle.ini.template
to /usr/local/share/braintacle/config/braintacle.ini and adjust its content
according to the comments within the file. This file must be readable by the
webserver, but should not be readable for the rest of the world. For example, if
the webserver runs in the 'www-data' group:

    chown root:www-data /usr/local/share/braintacle/config/database.ini
    chmod 640 /usr/local/share/braintacle/config/database.ini

If you prefer all config files within /etc or /usr/local/etc, make
/usr/local/share/braintacle/config/database.ini a symbolic link to the actual
file.

To create and initialize the tables, log out from the database and run the
schema manager script:

    /usr/local/share/braintacle/tools/schema-manager.php

If everything ran correctly, you should now be able to log into the database
with the configured credentials and see the tables.


Set up the server component
----------------------------

The server component requires an Apache installation. Other web servers are not
supported because it heavily relies on mod_perl and its interface to Apache
internals.

mod\_perl2 is available as a native package for most GNU/Linux distributions:
*libapache2-mod-perl2* (Debian/Ubuntu), *mod\_perl* (Fedora), *apache2-mod_perl*
(Suse). A Perl interpreter is installed by default on most GNU/Linux
distributions. Some nonstandard perl modules are required. There are several
ways to install them, in preferred order:

1. As a native package from a GNU/Linux distribution, see the table below
2. Via the *cpan* command line utility
3. Manual download from [CPAN](http://cpan.org) (take care of dependencies for
   yourself)

<pre>
**Module**                | **Debian/Ubuntu**         | **Fedora**        | **SUSE**
--------------------------|---------------------------|-------------------|------------------
Compress::Zlib            | (already present)         | perl-IO-Compress  | ?
DBI                       | libdbi-perl               | perl-DBI          | perl-DBI
DBD::Pg (PostgreSQL only) | libdbd-pg-perl            | perl-DBD-Pg       | perl-DBD-Pg
DBD::mysql (MySQL only)   | libdbd-mysql-perl         | perl-DBD-MySQL    | perl-DBD-mysql
Apache::DBI               | libapache-dbi-perl        | perl-Apache-DBI   | perl-Apache-DBI
Date::Calc                | libdate-calc-perl         | perl-Date-Calc    | perl-Date-Calc
XML::Simple               | libxml-simple-perl        | perl-XML-Simple   | perl-XML-Simple
XML::PARSER               | libxml-parser-perl        | perl-XML-Parser   | perl-XML-Parser
Sys::Syslog (optional)    | (already present)         | (already present) | (already present)
</pre>

Sys::Syslog is only required if you want to use syslog instead of logging
directly to a file (see below).

There are 2 configuration files for the server component:

- **braintacle-server.conf** controls how Apache invokes the scripts, restricts
access etc.
- **braintacle-server-app.conf** contains application-specific configuration,
like database credentials and logging. This file must be readable by the
webserver, but should not be readable for the rest of the world. It is included
from braintacle-server.conf.

Create a copy of the sample configuration file
config/braintacle-server.conf.template and make it known to Apache. If you don't
run multiple virtual hosts or want to make the application accessible on all
virtual hosts, you can simply copy it to a directory where Apache will read it
(typically /etc/apache2/conf.d or similar):

    cp /usr/local/share/braintacle/config/braintacle-server.conf.template \
       /etc/apache2/conf.d/braintacle-server.conf

To limit the application to a particular virtual host, copy the file somewhere
else and include it in the `<VirtualHost>` block:

    Include /usr/local/share/braintacle/config/braintacle-server.conf

The `PerlSwitches` directive in that file has no effect inside a `<VirtualHost>`
section. Place it outside the section (i.e. as a global setting) to make it
work.

Create a copy of config/braintacle-server-app.conf.template in a place *outside*
the Apache configuration and restrict read access, for example:

    cp /usr/local/share/braintacle/config/braintacle-server-app.conf.template \
       /usr/local/share/braintacle/config/braintacle-server-app.conf
    chgrp www-data /usr/local/share/braintacle/config/braintacle-server-app.conf
    chmod 640 /usr/local/share/braintacle/config/braintacle-server-app.conf

Edit both files to match your environment. Do not edit the template files
directly as they will be overwritten upon upgrading.

It is important that mod\_perl is loaded before the file is included. This is the
case in most standard Apache setups where modules are set up before conf.d gets
evaluated. If mod_perl is loaded from a config file in the same directory, make
sure that its name comes before the Braintacle configuration file in
alphabetical order. Rename the file if necessary.

By default, all activity is logged to syslog. No further action is required for
this configuration. Note that the Sys::Syslog module is required to make this
work. It is typically present on \*NIX systems, so that it works out of the box.
On Windows, it could be installed manually, but it's difficult to get usable
output there. It is recommended to set up a log file manually on Windows. You
can also do this on \*NIX systems if you don't want to use syslog.

To set up a log file, create a directory for them with write permissions for the
web server, and possibly no read permissions for the rest of the world, for
example:

    mkdir /var/log/braintacle
    chown www-data:www-data /var/log/braintacle
    chmod 750 /var/log/braintacle

Now edit braintacle-server-app.conf and set `OCS_OPT_LOGPATH` to the directory 
you just created.

You may want to rotate the logs regularly to prevent them from growing
infinitely. A sample logrotate configuration file is shipped in
config/logrotate.template. Copy this file to /etc/logrotate.d/braintacle and
edit it to suit your needs.

To finish installation, reload the Apache configuration. Your system is now
ready to accept client connections.

Depending on your configuration, you may see some errors in the Apache log file
like this:

    braintacle-server: Can't load SOAP::Transport::HTTP* - Web service will be unavailable

These messages are harmless and can be ignored if you don't plan to use the
(unsupported) SOAP service.


Set up the administration console
---------------------------------

The administration console requires a web server capable of executing PHP
scripts. The following instructions assume Apache httpd. Any other web server
should work too, but the instructions would need to be adapted.

The console requires *mod_rewrite* and *mod_env* enabled in your Apache
configuration. Both modules are shipped with Apache httpd, but may need to be
enabled first. Refer to your distribution's documentation for details.

Create a copy of config/braintacle-console.conf.template and make it known to
Apache, either by placing it in a directory where Apache will read it or by
including it in a particular VirtualHost definition. Edit the file to suit your
needs.

To finish installation, reload the Apache configuration. The console is now
ready to use. The default account has the username 'admin' and the password
'admin'. The password should be changed immediately. Click on "Preferences",
then "Users". Here you can change the password and optionally the username and
also create additional accounts.


Set up the package directory
----------------------------

If you want to use Braintacle to deploy packages, create a directory with write
access for the web server. Uploaded packages will be stored in this directory.

    mkdir /var/lib/braintacle/download
    chown www-data:www-data /var/lib/braintacle/download
    chmod 775 /var/lib/braintacle/download

In the administration console, enter the path unter Preferences->Packages. In
the same dialog, you have to specify 2 URLs (1 for HTTP, 1 for HTTPS) which must
point to this directory. An Apache template
(config/braintacle-download.conf.template) is provided for this purpose.


Set up the clients for inventory
--------------------------------

Braintacle does not provide its own client application. On the client machines,
install either the
[OCS Inventory NG agent](http://www.ocsinventory-ng.org/en/download/download-agent.html) or
[FusionInventory Agent](http://www.fusioninventory.org/documentation/agent/installation/).
Refer to the agent documentation for details. The agent must be configured for
the URL of Braintacle's server component.

To be able to use agents other than the OCS Inventory NG agent, a file with
whitelisted agent names is required. Such a file is provided in
config/allowed-agents.template. Setup instructions are provided in that file.



Upgrading from previous versions
================================

To upgrade your installation to a new Braintacle version, copy the files over
the installation directory. To prevent keeping obsolete files, it is best to
delete the content (except for the config/ directory!) first. Additional
required steps will be noted in the [changelog](./CHANGELOG.txt). If you skipped
a release, follow the instructions for the skipped releases first.

A common upgrade step is the database schema update. This is done with the
schema-manager.php script:

    /usr/local/share/braintacle/tools/schema-manager.php

Although the schema update is usually safe, a database backup is recommended. It
is also safe to run the script even if there is nothing to update.

If you run Braintacle directly off a git tree, upgrading is very easy: you can
use the script tools/update-from-git.sh which pulls the latest code and invokes
schema-manager.php.



Migrating from OCS Inventory NG
===============================

As parts of Braintacle are originally derived from OCS Inventory NG, an existing
installation can be migrated to a certain degree. This is not well tested, and
some data may not be converted properly. It's usually safer to set up from
scratch.


Conflicts with unsupported features
-----------------------------------

Some features are not supported and assumed not to be used in the database:

- Braintacle does not implement different access privileges. Only administrators
  can log in, and newly created users will have admin privileges.

- Very old accounts with a cleartext password will not work. Change the password
  for these accounts to use them.

- Braintacle assumes exactly 1 download server per package. Delete additional
  server entries. Use the same server URL for all packages.

- The SOAP service us unsupported and untested.

See the [main documentation](./doc/index.html) for a more detailed description
of differences.


Converting the database
-----------------------

Backing up the database before conversion is strongly recommended. Run
schema-manager.php as documented above; it should be able to convert the
database.


Replacing the server component
------------------------------

The converted database is not backwards compatible and only works with
Braintacle's server component. All required Perl modules will already be
installed except for Date::Calc which might not yet be present. To prevent
accidental execution of the wrong scripts, the old code should be moved out of
Perl's include path. The Apache configuration should be removed and set up from
scratch.
