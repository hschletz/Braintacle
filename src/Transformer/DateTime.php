<?php

namespace Braintacle\Transformer;

use Attribute;
use Formotron\Attribute\TransformerServiceAttribute;
use Override;

/**
 * Parse input string into DateTimeImmutable using given format (default: use database platform format).
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class DateTime implements TransformerServiceAttribute
{
    public function __construct(private ?string $format = null) {}

    #[Override]
    public function getServiceName(): string
    {
        return DateTimeTransformer::class;
    }

    #[Override]
    public function getArguments(): array
    {
        return [$this->format];
    }
}
