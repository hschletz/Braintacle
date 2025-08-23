<?php

namespace Braintacle\Transformer;

use Attribute;
use Formotron\Attribute\TransformerAttribute;
use Override;

/**
 * Generate boolean by given rules.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class ToBool implements TransformerAttribute
{
    public function __construct(private mixed $trueValue, private mixed $falseValue)
    {
        assert($trueValue !== $falseValue);
    }

    #[Override]
    public function transform(mixed $value): mixed
    {
        return match ($value) {
            $this->trueValue => true,
            $this->falseValue => false,
        };
    }
}
