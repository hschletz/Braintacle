<?php

namespace Braintacle\Package;

use Braintacle\CsrfProcessor;
use Braintacle\Package\Build\FormValidator;
use Formotron\Attribute\KeyOnly;
use Formotron\Attribute\PostProcess;
use Formotron\Attribute\PreProcess;

/**
 * Package update form data.
 */
#[PreProcess(CsrfProcessor::class)]
#[PostProcess(FormValidator::class)]
final class PackageUpdate extends Package
{
    #[KeyOnly]
    public bool $deployPending;

    #[KeyOnly]
    public bool $deployRunning;

    #[KeyOnly]
    public bool $deploySuccess;

    #[KeyOnly]
    public bool $deployError;

    #[KeyOnly]
    public bool $deployGroups;
}
