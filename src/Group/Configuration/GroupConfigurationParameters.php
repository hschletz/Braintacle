<?php

namespace Braintacle\Group\Configuration;

use Braintacle\Configuration\ConfigurationParameters;
use Braintacle\CsrfProcessor;
use Formotron\Attribute\PreProcess;

/**
 * Form data for group configuration.
 */
#[PreProcess(CsrfProcessor::class)]
final class GroupConfigurationParameters extends ConfigurationParameters
{
}
