<?php

namespace Braintacle\Transformer;

use Attribute;
use Formotron\Attribute\Transform;

/**
 * Parse input string into DateTimeImmutable using given format.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class DateTime extends Transform
{
    public function __construct(string $format)
    {
        parent::__construct(DateTimeTransformer::class, $format);
    }
}
