<?php

namespace Braintacle\Duplicates;

/**
 * Criteria for duplicates.
 */
enum Criterion: string
{
    case Name = 'name';
    case MacAddress = 'mac_address';
    case Serial = 'serial';
    case AssetTag = 'asset_tag';
}
