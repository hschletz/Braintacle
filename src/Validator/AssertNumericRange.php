<?php

namespace Braintacle\Validator;

use Attribute;
use Formotron\Attribute\ValidatorAttribute;
use InvalidArgumentException;
use Override;

/**
 * Validate value within given numeric range.
 *
 * Both minimum and maximum values are optional, but at least one of them must
 * be set.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class AssertNumericRange implements ValidatorAttribute
{
    public function __construct(private ?int $min = null, private ?int $max = null)
    {
        assert($min !== null || $max !== null); // At least 1 non-null argument
        assert($min === null || $max === null || $min < $max); // Compare if both arguments are set
    }

    #[Override]
    public function validate(mixed $value): void
    {
        if (!is_int($value)) {
            throw new InvalidArgumentException('Expected int, got ' . gettype($value));
        }
        if ($this->min !== null && $value < $this->min) {
            throw new InvalidArgumentException(sprintf('Value %d is less than %d', $value, $this->min));
        }
        if ($this->max !== null && $value > $this->max) {
            throw new InvalidArgumentException(sprintf('Value %d is greater than %d', $value, $this->max));
        }
    }
}
