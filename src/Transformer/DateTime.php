<?php

namespace Braintacle\Transformer;

use Attribute;
use Formotron\Attribute\Transform;

/**
 * Parse input string into DateTimeImmutable using given format (default: use database platform format).
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class DateTime extends Transform
{
    public function __construct(?string $format = null)
    {
        parent::__construct(DateTimeTransformer::class, $format);
    }
}
