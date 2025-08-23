<?php

namespace Braintacle\Validator;

use Attribute;
use Formotron\Attribute\ValidatorAttribute;
use InvalidArgumentException;
use Override;

/**
 * Validate string length.
 *
 * NULL values are accepted if $min is 0. To allow only strings that may be
 * empty, constrain the target's datatype to be non-nullable.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class AssertStringLength implements ValidatorAttribute
{
    public function __construct(private int $min, private ?int $max = null)
    {
        assert($min >= 0);
        assert($max === null || ($max >= 1 && $max >= $min));
    }

    #[Override]
    public function validate(mixed $value): void
    {
        if ($value === null && $this->min == 0) {
            return;
        }
        if (!is_string($value)) {
            throw new InvalidArgumentException('Expected string, got ' . gettype($value));
        }

        $length = mb_strlen($value);
        if ($length < $this->min) {
            throw new InvalidArgumentException(sprintf('String length %d is lower than %d', $length, $this->min));
        }
        if ($this->max !== null && $length > $this->max) {
            throw new InvalidArgumentException(sprintf('String length %d is higher than %d', $length, $this->max));
        }
    }
}
