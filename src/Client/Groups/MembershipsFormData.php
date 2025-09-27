<?php

namespace Braintacle\Client\Groups;

use Braintacle\CsrfProcessor;
use Braintacle\Group\Membership;
use Formotron\Attribute\PreProcess;
use Formotron\Attribute\Transform;

/**
 * Form data for managing a client's group membership.
 */
#[PreProcess(CsrfProcessor::class)]
class MembershipsFormData
{
    /**
     * Map of group names to membership types.
     * @var array<string, Membership>
     */
    #[Transform(GroupsTransformer::class)]
    public array $groups;
}
