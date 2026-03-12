<?php

namespace Braintacle\Package;

use Braintacle\CsrfProcessor;
use Braintacle\Package\Build\FormValidator;
use Braintacle\Transformer\TrimAndNullify;
use Braintacle\Validator\AssertNumericRange;
use Braintacle\Validator\AssertStringLength;
use Formotron\Attribute\KeyOnly;
use Formotron\Attribute\PostProcess;
use Formotron\Attribute\PreProcess;
use Formotron\Attribute\UseBackingValue;

/**
 * Package build form data.
 */
#[PreProcess(CsrfProcessor::class)]
#[PostProcess(FormValidator::class)]
class Package
{
    #[TrimAndNullify]
    #[AssertStringLength(min: 1, max: 255)]
    public string $name;

    #[TrimAndNullify]
    public ?string $comment;

    #[UseBackingValue]
    public Platform $platform;

    #[UseBackingValue]
    public Action $action;

    #[TrimAndNullify]
    public string $actionParam;

    #[AssertNumericRange(min: 0, max: 10)]
    public int $priority;

    #[TrimAndNullify]
    #[AssertNumericRange(min: 0)]
    public ?int $maxFragmentSize;

    #[KeyOnly]
    public bool $warn;

    #[TrimAndNullify]
    public ?string $warnMessage;

    #[TrimAndNullify]
    #[AssertNumericRange(min: 1)]
    public ?int $warnCountdown;

    #[KeyOnly]
    public bool $warnAllowAbort;

    #[KeyOnly]
    public bool $warnAllowDelay;

    #[TrimAndNullify]
    public ?string $postInstMessage;

    /**
     * Convert to array for legacy package builder.
     *
     * @deprecated Evaluate properties directly.
     */
    public function toArray(): array
    {
        return [
            'Name' => $this->name,
            'Comment' => $this->comment,
            'Platform' => $this->platform->value,
            'DeployAction' => $this->action->value,
            'ActionParam' => $this->actionParam,
            'Priority' => $this->priority,
            'MaxFragmentSize' => $this->maxFragmentSize,
            'Warn' => $this->warn,
            'WarnCountdown' => $this->warnCountdown ?? '',
            'WarnMessage' => $this->warnMessage,
            'WarnAllowAbort' => $this->warnAllowAbort,
            'WarnAllowDelay' => $this->warnAllowDelay,
            'PostInstMessage' => $this->postInstMessage,
        ];
    }
}
