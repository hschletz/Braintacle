<?php

namespace Braintacle\Package\PackageList;

/**
 * Package column mapping.
 */
enum PackageListColumn
{
    case Name;
    case Timestamp;
    case Size;
    case Platform;
    case NumPending;
    case NumRunning;
    case NumSuccess;
    case NumError;
}
