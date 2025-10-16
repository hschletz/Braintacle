<?php

namespace Braintacle\Group;

use DateTimeImmutable;
use Formotron\Transformer;
use Model\Config;
use Override;

final class CacheExpirationTransformer implements Transformer
{
    public function __construct(private Config $config) {}

    #[Override]
    public function transform(mixed $value, array $args): mixed
    {
        assert(empty($args));
        assert(is_int($value));

        return $value
            ? new DateTimeImmutable('@' . ($value + $this->config->groupCacheExpirationInterval))
            : null;
    }
}
