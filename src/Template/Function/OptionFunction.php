<?php

namespace Braintacle\Template\Function;

use Model\Config;

/**
 * Get access to global configuration.
 *
 * Passing config values through the template context is more straightforward in
 * most cases, but this function provides an alternative access method where
 * this is not feasible.
 */
final class OptionFunction
{
    public function __construct(private Config $config) {}

    public function __invoke(string $option): string | int
    {
        return $this->config->$option;
    }
}
