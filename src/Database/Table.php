<?php

namespace Braintacle\Database;

/**
 * Constants for table names.
 *
 * Many database identifiers are unclear, misleading or incorrect. Renaming them
 * would require more patches to the server code, making it harder to maintain.
 * This class defines table names as meaningful constants which should be used
 * instead of actual table names.
 *
 * Some tables serve more than one purpose, making them difficult to describe by
 * a single name. Multiple constants are defined for these tables. Code using
 * that table should pick the appropriate constant for the particular purpose.
 */

final class Table
{
    public const AndroidEnvironments = 'javainfos';

    /**
     * Config usage of "devices" table.
     */
    public const ClientConfig = 'devices';

    /**
     * "clients" view.
     *
     * This view provides all clients and should be used for any SELECT queries
     * on clients. It contains data from the "hardware" and "bios" tables,
     * excluding group entries, Windows-specific data and unused columns.
     */
    public const Clients = 'clients';

    public const ClientSystemInfo = 'bios';

    /**
     * Client usage of "hardware" table.
     */
    public const ClientTable = 'hardware';

    public const CustomFields = 'accountinfo';

    public const GroupMemberships = 'groups_cache';

    /**
     * Group usage of "hardware" table.
     */
    public const Groups = 'hardware';

    public const NetworkDevicesIdentified = 'network_devices';

    public const NetworkDevicesScanned = 'netmap';

    public const NetworkInterfaces = 'networks';

    /**
     * Package assignment usage of "devices" table.
     */
    public const PackageAssignments = 'devices';

    public const PackageHistory = 'download_history';

    public const Packages = 'download_available';

    /**
     * "braintacle_windows" table.
     *
     * Use only for setting manual_product_key. Use "windows_installations" view
     * for everything else.
     */
    public const WindowsProductKeys = 'braintacle_windows';
}
