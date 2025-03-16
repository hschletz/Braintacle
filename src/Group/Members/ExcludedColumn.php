<?php

namespace Braintacle\Group\Members;

/**
 * Columns on "excluded" page.
 */
enum ExcludedColumn: string
{
    case Id = 'id';
    case Name = 'name';
    case UserName = 'userid';
    case InventoryDate = 'lastdate';
}
