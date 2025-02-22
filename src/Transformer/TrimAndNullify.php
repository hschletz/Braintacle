<?php

namespace Braintacle\Transformer;

use Formotron\Transformer;
use InvalidArgumentException;
use Override;

/**
 * Trim string and convert empty string to NULL.
 */
class TrimAndNullify implements Transformer
{
    #[Override]
    public function transform(mixed $value, array $args): mixed
    {
        if (!is_string($value)) {
            throw new InvalidArgumentException('Expected string, got ' . gettype($value));
        }

        return trim($value) ?: null;
    }
}
