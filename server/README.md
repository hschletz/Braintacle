# Braintacle's patched version of OCS Inventory NG server

## About this version

This is a patched version of the [OCS inventory NG](https://ocsinventory-ng.org)
server that works with PostgeSQL in addition to MySQL and handles some
incompatible changes in Braintacle's database schema.

**This project is not provided by the OCS Inventory NG team. Do not bother them
with any issues you encounter with this version. Use Braintacle's issue tracker
instead.**

The agent that runs on the clients does not make any direct database
connections. All interaction runs through the communication server via
HTTP/HTTPS. No special version of the agent is required for the modified server.

## Differences to the original version

Besides the different database access methods, more changes have been made to the code:

- Log messages can optionally go to syslog or STDERR instead of an extra log file.
- The [officepack](https://github.com/PluginsOCSInventory-NG/officepack) plugin
  is supported out of the box.
- Workarounds for various agent bugs.
- The caching feature is disabled.
- The SOAP interface has been removed.
