<?php

namespace Braintacle\Transformer;

use DateTimeImmutable;
use DateTimeZone;
use Doctrine\DBAL\Connection;
use Formotron\Transformer;
use InvalidArgumentException;
use Override;

/**
 * Parse input value into DateTimeImmutable using given format (default: use database platform format).
 *
 * This is not extpcted to be used directly, but via the DateTime attribute.
 */
final class DateTimeTransformer implements Transformer
{
    public function __construct(private Connection $connection) {}

    /**
     * @param list{?string, ?DateTimeZone} $args
     */
    #[Override]
    public function transform(mixed $value, array $args): ?DateTimeImmutable
    {
        assert(count($args) == 2, 'Expected 2 arguments');
        assert(array_is_list($args), 'Expected arguments as list');

        [$format, $timezone] = $args;
        assert($timezone === null || $timezone instanceof DateTimeZone, 'not a DateTimeZone object');

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

        $result = DateTimeImmutable::createFromFormat($format, $value, $timezone);
        if (!$result) {
            throw new InvalidArgumentException(
                sprintf("Value '%s' cannot be parsed with format '%s'", $value, $format)
            );
        }

        return $result;
    }
}
