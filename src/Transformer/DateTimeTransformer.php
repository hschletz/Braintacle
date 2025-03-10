<?php

namespace Braintacle\Transformer;

use DateTimeImmutable;
use Formotron\Transformer;
use InvalidArgumentException;
use Override;

/**
 * Parse input string into DateTimeImmutable using given format.
 */
final class DateTimeTransformer implements Transformer
{
    #[Override]
    public function transform(mixed $value, array $args): DateTimeImmutable
    {
        assert(count($args) == 1);
        assert(isset($args[0]));
        $format = $args[0];
        assert(is_string($format));

        $result = DateTimeImmutable::createFromFormat($format, $value);
        if (!$result) {
            throw new InvalidArgumentException(
                sprintf("Value '%s' cannot be parsed with format '%s'", $value, $format)
            );
        }

        return $result;
    }
}
