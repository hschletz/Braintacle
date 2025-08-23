<?php

namespace Braintacle\Group\Add;

use Braintacle\CsrfProcessor;
use Braintacle\Group\Membership;
use Braintacle\Search\SearchParams;
use Braintacle\Transformer\TrimAndNullify;
use Braintacle\Validator\AssertStringLength;
use Formotron\Attribute\PreProcess;
use Formotron\Attribute\UseBackingValue;

/**
 * Form data for creating a group with given clients.
 */
#[PreProcess(CsrfProcessor::class)]
class NewGroupFormData extends SearchParams
{
    #[UseBackingValue]
    public Membership $membershipType;

    #[TrimAndNullify]
    #[AssertStringLength(min: 1, max: 255)]
    public string $name;

    #[TrimAndNullify]
    #[AssertStringLength(min: 0, max: 255)]
    public ?string $description;
}
