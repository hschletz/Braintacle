<?php

namespace Braintacle\Client\Software;

/**
 * Columns on Software page.
 */
enum SoftwareColumn: string
{
    case Name = 'name';
    case Version = 'version';
    case Publisher = 'publisher';
    case InstallLocation = 'installLocation';
    case Size = 'size';
    case Architecture = 'architecture';
}
