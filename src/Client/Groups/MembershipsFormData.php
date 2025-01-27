<?php

namespace Braintacle\Client\Groups;

use Braintacle\CsrfProcessor;
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
     * @var array<string, 0|1|2>
     */
    #[Transform(GroupsTransformer::class)]
    public array $groups;
}
