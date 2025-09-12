<?php

namespace Braintacle\Database;

/**
 * Constants for table names.
 *
 * Many database identifiers are unclear, misleading or incorrect. Renaming them
 * would require more patches to the server code, making it harder to maintain.
 * This class defines table names as meaningful constants which should be used
 * instead of actual table names.
 */

final class Table
{
    /**
     * "clients" view.
     *
     * This view provides all clients and should be used for any SELECT queries
     * on clients. It contains data from the "hardware" and "bios" tables,
     * excluding group entries, Windows-specific data and unused columns.
     */
    public const Clients = 'clients';

    public const GroupMemberships = 'groups_cache';

    /**
     * Group usage of "hardware" table.
     */
    public const Groups = 'hardware';

    public const PackageAssignments = 'devices';

    public const PackageHistory = 'download_history';

    public const Packages = 'download_available';
}
