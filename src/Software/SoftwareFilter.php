<?php

namespace Braintacle\Software;

/**
 * Software filters.
 */
enum SoftwareFilter: string
{
    /**
     * Manually accepted
     */
    case Accepted = 'accepted';

    /**
     * Manually ignored
     */
    case Ignored = 'ignored';

    /**
     * Neither accepted nor ignored
     */
    case New = 'new';

    /**
     * No filter
     */
    case All = 'all';
}
