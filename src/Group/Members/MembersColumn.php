<?php

namespace Braintacle\Group\Members;

/**
 * Columns on members page.
 */
enum MembersColumn: string
{
    case Id = 'id';
    case Name = 'name';
    case UserName = 'userid';
    case InventoryDate = 'lastdate';
    case Membership = 'static';
}
