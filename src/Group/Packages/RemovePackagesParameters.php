<?php

namespace Braintacle\Group\Packages;

use Braintacle\Group\GroupRequestParameters;
use Formotron\Attribute\Key;

/**
 * URI query parameters for removing packages from a group.
 */
class RemovePackagesParameters extends GroupRequestParameters
{
    #[Key('package')]
    public string $packageName;
}
