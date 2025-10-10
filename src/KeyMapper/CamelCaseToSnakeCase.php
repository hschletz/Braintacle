<?php

namespace Braintacle\KeyMapper;

use Formotron\KeyMapper;
use Override;

/**
 * Convert key from camelCase to snake_case.
 *
 * This implementation assumes valid camelCase input without consecutive
 * uppercase characters.
 */
final class CamelCaseToSnakeCase implements KeyMapper
{
    #[Override]
    public function getKey(string $property): string
    {
        return strtolower(preg_replace('/[A-Z]/', '_$0', $property));
    }
}
