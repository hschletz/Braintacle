<?php

namespace Braintacle\Package\Build;

use InvalidArgumentException;

/**
 * Catchable package validation errors.
 */
final class ValidationErrors extends InvalidArgumentException
{
    public function __construct(
        public readonly ?string $nameExistsMessage,
        public readonly ?string $warnMessageInvalidMessage,
        public readonly ?string $postInstMessageInvalidMessage,
    ) {
        parent::__construct('Invalid package data, see exception properties for details');
    }
}
