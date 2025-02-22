<?php

namespace Braintacle\Client\Groups;

use Braintacle\Group\Membership;
use Formotron\AssertionFailedException;
use Formotron\Transformer;
use Override;

/**
 * Validate and cast membership types.
 */
class GroupsTransformer implements Transformer
{
    #[Override]
    public function transform(mixed $value, array $args): mixed
    {
        if (!is_array($value)) {
            throw new AssertionFailedException('Expected map, got ' . gettype($value));
        }
        foreach ($value as $group => &$membership) {
            if (!is_string($group)) {
                throw new AssertionFailedException('Expected string, got ' . gettype($group));
            }
            $membership = Membership::from($membership)->value;
        }

        return $value;
    }
}
