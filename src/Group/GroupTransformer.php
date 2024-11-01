<?php

namespace Braintacle\Group;

use Formotron\Transformer;
use Model\Group\GroupManager;

/**
 * Transform group name to Group object.
 */
class GroupTransformer implements Transformer
{
    public function __construct(private GroupManager $groupManager)
    {
    }

    public function transform(mixed $value): mixed
    {
        return $this->groupManager->getGroup($value);
    }
}
