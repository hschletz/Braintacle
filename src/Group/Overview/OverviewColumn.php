<?php

namespace Braintacle\Group\Overview;

/**
 * Sortable columns for the groups overview table.
 */
enum OverviewColumn: string
{
    case Name = 'name';
    case CreationDate = 'lastdate';
    case Description = 'description';
}
