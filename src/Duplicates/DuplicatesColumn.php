<?php

namespace Braintacle\Duplicates;

/**
 * Column identifiers for duplicates table.
 */
enum DuplicatesColumn: string
{
    case Id = 'id';
    case Name = 'name';
    case MacAddress = 'mac_address';
    case Serial = 'serial';
    case AssetTag = 'asset_tag';
    case LastContactDate = 'last_contact';
}
