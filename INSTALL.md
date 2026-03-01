# Installing Braintacle

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

All these options can be changed, with the requirement of additional tweaking.
The above setup is recommended because this has been well tested and
installation is easier.


## Download and extract the files

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


## Set up dependencies

Install [Composer](https://getcomposer.org/). From the Braintacle root
directory, run:

    composer install --no-dev

This will download all dependencies and set up the autoloader. Omit the
`--no-dev` option if you want to run developnebt tools like unit tests or code
checkers.


## Set up the configuration file

The file config/braintacle.ini.template is a template for the configuration
file. Don't edit it directly because it may be overwritten upon upgrades. Copy,
move or rename it instead.

By default, Braintacle will look for a file config/braintacle.ini relative to
the Braintacle root directory. If you prefer a different location (/etc,
/usr/local/etc ...), you can set the BRAINTACLE_CONFIG environment variable to
the full path (including filename) of your config file. The command line tool
(braintacle-tool.php) additionally accepts an optional "--config" argument which
will take precedence over the environment variable or the default location.

The file must be readable by the webserver, but should not be readable for the
rest of the world if it contains a sensitive database password. For example, if
the webserver runs in the 'www-data' group:

    chown root:www-data /usr/local/share/braintacle/config/braintacle.ini
    chmod 640 /usr/local/share/braintacle/config/braintacle.ini

Edit your configuration according to the comments within the file. As a minimum,
the `dsn` parameter in the `[database]` section must be set up for the database
you're about to create in the next step. Everything else is mostly useful for
development purposes.


## Set up the database

Install a PostgreSQL or MySQL/MariaDB server, if not already available, and log
into that database server with superuser privileges (typically the 'postgres'
user for PostgreSQL, 'root' for MySQL). Create a new database user (if not
already present) and the database with access privileges for that user. The
database must use Unicode.

For PostgreSQL, run:

    CREATE USER username WITH PASSWORD 'passwd';
    CREATE DATABASE braintacle OWNER username ENCODING 'UTF8';

For MySQL, run:

    CREATE USER username IDENTIFIED BY 'passwd';
    CREATE DATABASE braintacle CHARACTER SET utf8mb4;
    GRANT ALL ON braintacle.* TO username;

You can choose any database name, user name and password.

To create and initialize the tables, log out from the database and run the
database manager script (the --config option can be omitted if your config file
resides in the default location or is set via the BRAINTACLE_CONFIG environment
variable, see previous section for details):

    braintacle-tool.php database --config=/etc/braintacle.ini

If everything ran correctly, you should now be able to log into the database
with the configured credentials and see the tables.


## Set up the server component

The server can be installed in 2 ways:

- With [Apache httpd](http://httpd.apache.org) and
  [mod_perl](https://perl.apache.org). This is well tested, but rather complex
  to set up and difficult to debug if anything goes wrong.

- As a CGI script. Performance will not be as good as with mod_perl, but that
  won't be an issue with moderate load (like a few dozen requests per hour). On
  the other hand, it has various advantages:

  - It runs on any webserver that can execute CGI scripts.
  - It is much simpler to set up.
  - Error tracking is much easier.
  - It can even be invoked manually (with the necessary CGI environment
    variables and request data) for development, testing and debugging, without
    a webserver.

In any case, the server component needs a Perl interpreter, which is installed by default on most GNU/Linux
distributions. Some nonstandard perl modules are required. There are several
ways to install them:

1. As a native package from a GNU/Linux distribution, see the table below
2. Via the *cpan* or *cpanm* command line utilities
3. Manual download from [CPAN](https://cpan.org) (take care of dependencies for
   yourself)

Module                      | Debian/Ubuntu             | Fedora
----------------------------|---------------------------|-------------------
CGI::Tiny (CGI only)        | libcgi-tiny-perl          | —
Compress::Zlib              | (already present)         | perl-IO-Compress
DBI                         | libdbi-perl               | perl-DBI
DBD::Pg (PostgreSQL only)   | libdbd-pg-perl            | perl-DBD-Pg
DBD::mysql (MySQL only)     | libdbd-mysql-perl         | perl-DBD-MySQL
Apache::DBI (mod_perl only) | libapache-dbi-perl        | perl-Apache-DBI
Date::Calc                  | libdate-calc-perl         | perl-Date-Calc
XML::Simple                 | libxml-simple-perl        | perl-XML-Simple
XML::PARSER                 | libxml-parser-perl        | perl-XML-Parser
Sys::Syslog (optional)      | (already present)         | (already present)

Sys::Syslog is only required if you want to use syslog instead of logging to a
file or STDERR (see below).

### Installation as a mod_perl handler

mod\_perl2 is available as a native package for most GNU/Linux distributions:
*libapache2-mod-perl2* (Debian/Ubuntu), *mod\_perl* (Fedora).

There are 2 configuration files for the server component:

- **braintacle-server.conf** controls how Apache httpd invokes the scripts,
restricts access etc.
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

It is important that mod\_perl is loaded before the file is included. This is
the case in most standard configurations where modules are set up before conf.d
gets evaluated. If mod_perl is loaded from a config file in the same directory,
make sure that its name comes before the Braintacle configuration file in
alphabetical order. Rename the file if necessary.

### Installation as a CGI handler

Refer to your webserver's documentation for setting up a CGI handler. Database
configuration is set up through environment variables, which should be set up
for this application only. If the config file contains database credentials, it
should be readable only for the web server user.

The following configuration for Apache httpd would make the application
available under `http://example.net/braintacle-server/:`

```
ScriptAlias /braintacle-server/ /usr/local/share/braintacle/server/braintacle-server.cgi

<Directory /usr/local/share/braintacle/server/>
    Require all granted

    SetEnv OCS_DB_TYPE Pg # Name of the DBD driver: Pg, mysql or sqlite
    SetEnv OCS_DB_NAME braintacle
    SetEnv OCS_DB_HOST 127.0.0.1
    SetEnv OCS_DB_PORT 5432
    SetEnv OCS_DB_USER username
    SetEnv OCS_DB_PWD password
<Directory>
```

### Configuring logging

The content of the `OCS_OPT_LOGPATH` environment variable controls the
application's logging target:

- `syslog`: This is the default. Messages are written to the system's syslog
  service. The Sys::Syslog module is required to make this work. It is typically
  present on \*NIX systems.
- `stderr`: Messages are written to STDERR. The webserver is responsible for
  capturing the script`s STDERR output. It will typically forward messages to
  its own logger. This is also useful for manual ivocation from a terminal.
- Any other value should be a directory with write permissions for the web
  server, and possibly no read permissions for the rest of the world. The
  application will create a file `activity.log` within this directory and write
  messages to this file. You may want to rotate the logs regularly to prevent
  them from growing infinitely. A sample logrotate configuration file is shipped
  in config/logrotate.template. Copy this file to /etc/logrotate.d/braintacle
  and edit it to suit your needs.

### Testing connectivity

The server should now be ready to accept connections from a client. If the
webserver has not been set up correctly, finding the root cause for connectivity
problems can be difficult this way.

As a simple test, you can send a GET request to the server:

```
curl -v http://example.net/braintacle-server/
```

If you receive a 405 (Method Not Allowed) response, the webserver has
successfully invoked the application (it accepts only POST requests, hence the
405 response). For any other status code (most likely 403, 404 or 500), the
reason should show up in the webserver logs.


## Set up the administration console

The administration console requires a web server capable of executing PHP
scripts. The following instructions assume Apache httpd. Any other web server
should work too, but the instructions would need to be adapted.

The console requires *mod_rewrite* and *mod_env*. Both modules are shipped with
Apache httpd, but may need to be enabled first. Refer to your distribution's
documentation for details.

Create a copy of config/braintacle-console.conf.template and add it to your
config, either by placing it in a directory where it will be evaluated, or by
including it in a particular VirtualHost definition. Edit the file to suit your
needs.

The console is now ready to use. The default account has the username 'admin'
and the password 'admin'. The password should be changed immediately. Click on
"Preferences", then "Users". Here you can change the password and optionally the
username and also create additional accounts.


## Set up the package directory

If you want to use Braintacle to deploy packages, create a directory with write
access for the web server. Uploaded packages will be stored in this directory.

    mkdir /var/lib/braintacle/download
    chown www-data:www-data /var/lib/braintacle/download
    chmod 775 /var/lib/braintacle/download

In the administration console, enter the path unter Preferences->Download. In
the same dialog, you have to specify 2 URLs (1 for HTTP, 1 for HTTPS) which must
point to this directory. A config template
(config/braintacle-download.conf.template) is provided for this purpose. The
default path (unless configured differently in braintacle-download.conf) is
/braintacle-download, i.e. `http://example.net/braintacle-download`.


## Set up the clients for inventory

Braintacle does not provide its own client application. On the client machines,
install the [OCS Inventory NG agent](https://github.com/OCSInventory-NG/) for
your client OS. Refer to the agent documentation for details. The agent must be
configured for the URL of Braintacle's server component which you set up
earlier.

To be able to use agents other than the OCS Inventory NG agent, a file with
whitelisted agent names is required. Such a file is provided in
config/allowed-agents.template. Setup instructions are provided in that
file.



## Upgrading from previous versions

To upgrade your installation to a new Braintacle version, copy the files over
the installation directory. To prevent keeping obsolete files, it is best to
delete the content (except for the config/ directory!) first. Additional
required steps will be noted in the [changelog](./CHANGELOG.txt). If you skipped
a release, follow the instructions for the skipped releases first.

A common upgrade step is the database schema update. This is done with the
database manager script:

    /usr/local/share/braintacle/braintacle-tool.php database

Although the schema update is usually safe, a database backup is recommended. It
is also safe to run the script even if there is nothing to update.
