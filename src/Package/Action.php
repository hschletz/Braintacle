<?php

namespace Braintacle\Package;

/**
 * Package actions.
 */
enum Action: string
{
    /**
     * Download package, execute command, retrieve result.
     */
    case Launch = 'launch';

    /**
     * Optionally download package, execute command.
     */
    case Execute = 'execute';

    /**
     * Just download package to target path.
     */
    case Store = 'store';
}
