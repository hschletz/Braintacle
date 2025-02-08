<?php

namespace Braintacle\Transformer;

use Formotron\Transformer;

/**
 * Generate boolean by given rules.
 */
class ToBoolTransformer implements Transformer
{
    public function transform(mixed $value, array $args): mixed
    {
        assert(array_key_exists('trueValue', $args));
        assert(array_key_exists('falseValue', $args));

        $trueValue = $args['trueValue'];
        $falseValue = $args['falseValue'];
        assert($trueValue !== $falseValue);

        return match ($value) {
            $trueValue => true,
            $falseValue => false,
        };
    }
}
