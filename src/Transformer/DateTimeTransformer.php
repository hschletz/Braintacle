<?php

namespace Braintacle\Transformer;

use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Formotron\Transformer;
use InvalidArgumentException;
use Override;

/**
 * Parse input value into DateTimeImmutable using given format (default: use database platform format).
 */
final class DateTimeTransformer implements Transformer
{
    public function __construct(private Connection $connection) {}

    #[Override]
    public function transform(mixed $value, array $args): ?DateTimeImmutable
    {
        assert(count($args) <= 1);
        $format = current($args) ?: null;
        if ($format === null) {
            $format = $this->connection->getDatabasePlatform()->getDateTimeFormatString();
        }
        assert(is_string($format));

        if ($value === null) {
            return null;
        }
        if ($format == 'U') {
            assert(is_int($value));
            // 0 will never mean 1970-01-01, but is the result of a bad
            // definition or faulty conversion. NULL is the appropriate result
            // in this case.
            if ($value === 0) {
                return null;
            }
        } else {
            assert(is_string($value));
        }

        $result = DateTimeImmutable::createFromFormat($format, $value);
        if (!$result) {
            throw new InvalidArgumentException(
                sprintf("Value '%s' cannot be parsed with format '%s'", $value, $format)
            );
        }

        return $result;
    }
}
