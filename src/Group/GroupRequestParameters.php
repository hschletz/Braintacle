<?php

namespace Braintacle\Group;

use Formotron\Attribute\Key;
use Formotron\Attribute\Transform;

/**
 * URI query parameters for group actions.
 */
class GroupRequestParameters
{
    #[Key('name')]
    #[Transform(GroupTransformer::class)]
    public Group $group;
}
