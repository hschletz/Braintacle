<?php

namespace Braintacle\Group\Add;

use Braintacle\CsrfProcessor;
use Braintacle\Group\GroupTransformer;
use Braintacle\Group\Membership;
use Braintacle\Search\SearchParams;
use Formotron\Attribute\PreProcess;
use Formotron\Attribute\Transform;
use Formotron\Attribute\UseBackingValue;
use Model\Group\Group;

/**
 * Form data for setting clients on an existing group.
 */
#[PreProcess(CsrfProcessor::class)]
class ExistingGroupFormData extends SearchParams
{
    #[UseBackingValue]
    public Membership $membershipType;

    #[Transform(GroupTransformer::class)]
    public Group $group;
}
