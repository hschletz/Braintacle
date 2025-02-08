<?php

namespace Braintacle\Validator;

use Formotron\AssertionFailedException;
use Formotron\Validator;
use InvalidArgumentException;

/**
 * Validate string length.
 *
 * NULL values are accepted if $min is 0. To allow only strings that may be
 * empty, constrain the target's datatype to be non-nullable.
 */
class StringLengthValidator implements Validator
{
    public function getValidationErrors(mixed $value, array $args): array
    {
        assert(array_key_exists('min', $args));
        assert(array_key_exists('max', $args));
        $min = $args['min'];
        $max = $args['max'];
        assert(is_int($min) && $min >= 0);
        assert($max === null || is_int($max) && $max >= $min);

        if ($value === null && $min == 0) {
            return [];
        }
        if (!is_string($value)) {
            throw new InvalidArgumentException('Expected string, got ' . gettype($value));
        }

        $length = mb_strlen($value);
        if ($length < $min) {
            throw new AssertionFailedException(sprintf('String length %d is lower than %d', $length, $min));
        }
        if ($max !== null && $length > $max) {
            throw new AssertionFailedException(sprintf('String length %d is higher than %d', $length, $max));
        }

        return [];
    }
}
