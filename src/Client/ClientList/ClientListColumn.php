<?php

namespace Braintacle\Client\ClientList;

/**
 * Client column mapping.
 */
enum ClientListColumn: string
{
    case Name = 'name';
    case UserName = 'userid';
    case OsName = 'osname';
    case Type = 'type';
    case CpuClock = 'processors';
    case PhysicalMemory = 'memory';
    case InventoryDate = 'lastdate';
}
