<?php

namespace Braintacle\Client\Packages;

use Braintacle\Client\ClientRequestParameters;
use Formotron\Attribute\Key;

/**
 * URI query parameters for package actions (remove/reset) on a client.
 */
class PackageActionParameters extends ClientRequestParameters
{
    #[Key('package')]
    public string $packageName;
}
