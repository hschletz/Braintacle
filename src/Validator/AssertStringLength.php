<?php

namespace Braintacle\Validator;

use Attribute;
use Formotron\Attribute\Assert;

/**
 * Validate string length.
 *
 * NULL values are accepted if $min is 0. To allow only strings that may be
 * empty, constrain the target's datatype to be non-nullable.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class AssertStringLength extends Assert
{
    public function __construct(int $min, ?int $max = null)
    {
        parent::__construct(StringLengthValidator::class, min: $min, max: $max);
    }
}
