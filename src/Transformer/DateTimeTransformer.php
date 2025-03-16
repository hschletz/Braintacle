<?php

namespace Braintacle\Transformer;

use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Formotron\Transformer;
use InvalidArgumentException;
use Override;

/**
 * Parse input string into DateTimeImmutable using given format (default: use database platform format).
 */
final class DateTimeTransformer implements Transformer
{
    public function __construct(private Connection $connection) {}

    #[Override]
    public function transform(mixed $value, array $args): DateTimeImmutable
    {
        assert(is_string($value));
        assert(count($args) <= 1);
        $format = current($args) ?: null;
        if ($format === null) {
            $format = $this->connection->getDatabasePlatform()->getDateTimeFormatString();
        }
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
