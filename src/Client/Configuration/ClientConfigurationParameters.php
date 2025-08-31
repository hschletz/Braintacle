<?php

namespace Braintacle\Client\Configuration;

use Braintacle\Configuration\ConfigurationParameters;
use Braintacle\CsrfProcessor;
use Braintacle\Transformer\TrimAndNullify;
use Braintacle\Validator\AssertIpAddress;
use Formotron\Attribute\PreProcess;

/**
 * Form data for client configuration.
 */
#[PreProcess(CsrfProcessor::class)]
final class ClientConfigurationParameters extends ConfigurationParameters
{
    #[TrimAndNullify]
    #[AssertIpAddress]
    public ?string $scanThisNetwork;
}
