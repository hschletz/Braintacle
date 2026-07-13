<?php

namespace Braintacle\Client\Registry;

/**
 * Registry root keys, backed by database representation.
 */
enum RootKey: int
{
    case HKEY_CLASSES_ROOT = 0;
    case HKEY_CURRENT_USER = 1;
    case HKEY_LOCAL_MACHINE = 2;
    case HKEY_USERS = 3;
    case HKEY_CURRENT_CONFIG = 4;
}
