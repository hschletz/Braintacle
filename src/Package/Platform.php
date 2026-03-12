<?php

namespace Braintacle\Package;

/**
 * Package target platforms.
 */
enum Platform: string
{
    case Windows = 'windows';
    case Linux = 'linux';
    case MacOs = 'mac';
}
