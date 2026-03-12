<?php

namespace Braintacle\Package\Build;

use Braintacle\Package\Package;
use Braintacle\Package\Platform;
use Formotron\PostProcessor;
use Laminas\Translator\TranslatorInterface;
use Model\Package\PackageManager;
use Override;

/**
 * Advanced package form validations that should be reported indivdually.
 */
final class FormValidator implements PostProcessor
{
    public function __construct(private PackageManager $packageManager, private TranslatorInterface $translator) {}

    #[Override]
    public function process(object $dataObject): void
    {
        assert($dataObject instanceof Package);

        $nameExists = preg_grep(
            '/^' . preg_quote($dataObject->name, '/') . '$/ui',
            $this->packageManager->getAllNames()
        );

        // The Windows agent handles notification messages through a separate
        // application (OcsNotifyUser.exe). Its command line parser does not
        // support double quotes in arguments. Other agents do not support user
        // notifications and ignore the message. Do not validate in this case
        // because the message will be hidden.
        if ($dataObject->platform == Platform::Windows) {
            $warnMessageInvalid = str_contains($dataObject->warnMessage ?? '', '"');
            $postInstMessageInvalid = str_contains($dataObject->postInstMessage ?? '', '"');
        } else {
            $warnMessageInvalid = false;
            $postInstMessageInvalid = false;
        }

        if ($nameExists || $warnMessageInvalid || $postInstMessageInvalid) {
            throw new ValidationErrors(
                $nameExists ? $this->translator->translate(
                    'A package with this name already exists.'
                ) : null,
                $warnMessageInvalid ? $this->translator->translate(
                    'Message must not contain double quotes.'
                ) : null,
                $postInstMessageInvalid ? $this->translator->translate(
                    'Message must not contain double quotes.'
                ) : null,
            );
        }
    }
}
