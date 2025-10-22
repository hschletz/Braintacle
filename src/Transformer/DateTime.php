<?php

namespace Braintacle\Transformer;

use Attribute;
use DateTimeZone;
use Formotron\Attribute\TransformerServiceAttribute;
use Override;

/**
 * Parse input string into DateTimeImmutable using given format (default: use database platform format).
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class DateTime implements TransformerServiceAttribute
{
    public const Database = null;
    public const Epoch = 'U';

    private ?DateTimeZone $timezone;

    public function __construct(private ?string $format = null, ?string $timezone = null)
    {
        $this->timezone = ($timezone === null) ? null : new DateTimeZone($timezone);
    }

    #[Override]
    public function getServiceName(): string
    {
        return DateTimeTransformer::class;
    }

    #[Override]
    public function getArguments(): array
    {
        return [$this->format, $this->timezone];
    }
}
