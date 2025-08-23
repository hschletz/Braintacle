<?php

namespace Braintacle\Validator;

use Attribute;
use Formotron\Attribute\ValidatorAttribute;
use InvalidArgumentException;
use Override;

/**
 * Validate string list (list<string>)
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class IsStringList implements ValidatorAttribute
{
    #[Override]
    public function validate(mixed $value): void
    {
        if (!array_is_list($value)) {
            throw new InvalidArgumentException('Input array is not a list');
        }
        foreach ($value as $element) {
            if (!is_string($element)) {
                throw new InvalidArgumentException('Input array contains non-string elements');
            }
        }
    }
}
