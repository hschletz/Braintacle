<?php

namespace Braintacle\Transformer;

use Attribute;
use Formotron\Attribute\TransformerAttribute;
use InvalidArgumentException;
use Override;

/**
 * Trim string and convert empty string to NULL.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class TrimAndNullify implements TransformerAttribute
{
    #[Override]
    public function transform(mixed $value): mixed
    {
        if (!is_string($value)) {
            throw new InvalidArgumentException('Expected string, got ' . gettype($value));
        }

        return trim($value) ?: null;
    }
}
