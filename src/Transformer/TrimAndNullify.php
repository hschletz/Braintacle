<?php

namespace Braintacle\Transformer;

use Formotron\Transformer;
use InvalidArgumentException;

/**
 * Trim string and convert empty string to NULL.
 */
class TrimAndNullify implements Transformer
{
    public function transform(mixed $value, array $args): mixed
    {
        if (!is_string($value)) {
            throw new InvalidArgumentException('Expected string, got ' . gettype($value));
        }

        return trim($value) ?: null;
    }
}
