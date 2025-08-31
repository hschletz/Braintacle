<?php

namespace Braintacle\Configuration;

use Braintacle\Transformer\TrimAndNullify;
use Braintacle\Validator\AssertNumericRange;
use Formotron\Attribute\KeyOnly;

/**
 * Common properties for client/group configuration.
 */
abstract class ConfigurationParameters
{
    #[TrimAndNullify]
    #[AssertNumericRange(min: 1)]
    public ?int $contactInterval;

    #[TrimAndNullify]
    #[AssertNumericRange(min: -1)]
    public ?int $inventoryInterval;

    #[KeyOnly]
    public bool $packageDeployment;

    #[TrimAndNullify]
    #[AssertNumericRange(min: 1)]
    public ?int $downloadPeriodDelay;

    #[TrimAndNullify]
    #[AssertNumericRange(min: 1)]
    public ?int $downloadCycleDelay;

    #[TrimAndNullify]
    #[AssertNumericRange(min: 1)]
    public ?int $downloadFragmentDelay;

    #[TrimAndNullify]
    #[AssertNumericRange(min: 0, max: 10)]
    public ?int $downloadMaxPriority;

    #[TrimAndNullify]
    #[AssertNumericRange(min: 1)]
    public ?int $downloadTimeout;

    #[KeyOnly]
    public bool $allowScan;

    #[KeyOnly]
    public bool $scanSnmp;
}
