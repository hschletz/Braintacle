<?php

namespace Braintacle\Transformer;

use Attribute;
use Formotron\Attribute\Transform;

/**
 * Generate boolean by given rules.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class ToBool extends Transform
{
    public function __construct(mixed $trueValue, mixed $falseValue)
    {
        parent::__construct(ToBoolTransformer::class, trueValue: $trueValue, falseValue: $falseValue);
    }
}
