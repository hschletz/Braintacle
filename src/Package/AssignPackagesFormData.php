<?php

namespace Braintacle\Package;

use Braintacle\CsrfProcessor;
use Braintacle\Validator\IsStringList;
use Formotron\Attribute\Key;
use Formotron\Attribute\PreProcess;

/**
 * Form data for assigning packages to a client or group.
 */
#[PreProcess(CsrfProcessor::class)]
final class AssignPackagesFormData
{
    /** @var string[] */
    #[Key('packages')]
    #[IsStringList]
    public array $packageNames = [];
}
