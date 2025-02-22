<?php

namespace Braintacle\Group;

use Formotron\Transformer;
use Model\Group\GroupManager;
use Override;

/**
 * Transform group name to Group object.
 */
class GroupTransformer implements Transformer
{
    public function __construct(private GroupManager $groupManager) {}

    #[Override]
    public function transform(mixed $value, array $args): mixed
    {
        return $this->groupManager->getGroup($value);
    }
}
