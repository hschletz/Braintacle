<?php

namespace Braintacle\Software;

use Braintacle\CsrfProcessor;
use Braintacle\Validator\IsStringList;
use Formotron\Attribute\Assert;
use Formotron\Attribute\PreProcess;

/**
 * Software management form data.
 */
#[PreProcess(CsrfProcessor::class)]
class SoftwareFormData
{
    #[Assert(IsStringList::class)]
    public array $software = [];

    public Action $action;
}
