<?php

namespace Braintacle\Group;

use Formotron\Transformer;
use Override;

/**
 * Transform group name to Group object.
 */
class GroupTransformer implements Transformer
{
    public function __construct(private Groups $groups) {}

    #[Override]
    public function transform(mixed $value, array $args): mixed
    {
        return $this->groups->getGroup($value);
    }
}
