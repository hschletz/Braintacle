<?php

namespace Braintacle\Test\Package\Build;

use AssertionError;
use Braintacle\Package\Build\FormValidator;
use Braintacle\Package\Build\ValidationErrors;
use Braintacle\Package\Package;
use Braintacle\Package\Platform;
use Laminas\Translator\TranslatorInterface;
use Model\Package\PackageManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use stdClass;

#[CoversClass(FormValidator::class)]
#[UsesClass(ValidationErrors::class)]
final class FormValidatorTest extends TestCase
{
    private function createValidator(
        ?TranslatorInterface $translator = null,
    ): FormValidator {
        $packageManager = $this->createStub(PackageManager::class);
        $packageManager->method('getAllNames')->willReturn(['existing/package']);

        return new FormValidator(
            $packageManager,
            $translator ?? $this->createStub(TranslatorInterface::class),
        );
    }

    public function testInvalidClass()
    {
        $this->expectException(AssertionError::class);
        $this->createValidator()->process(new stdClass());
    }

    #[TestWith(['existing/package/2', null, null, Platform::Linux])]
    #[TestWith(['name', '"', '"', Platform::Linux])]
    #[TestWith(['name', 'msg', 'msg', Platform::Windows])]
    #[DoesNotPerformAssertions]
    public function testValid(string $name, ?string $warnMessage, ?string $postInstMessage, Platform $platform)
    {
        $package = new Package();
        $package->name = $name;
        $package->warnMessage = $warnMessage;
        $package->postInstMessage = $postInstMessage;
        $package->platform = $platform;

        $this->createValidator()->process($package);
    }

    #[TestWith(['existing/package', null, null, '_A package with this name already exists.', null, null])]
    #[TestWith(['name', '"', null, null, '_Message must not contain double quotes.', null])]
    #[TestWith(['name', null, '"', null, null, '_Message must not contain double quotes.'])]
    #[TestWith([
        'existing/package',
        '"',
        '"',
        '_A package with this name already exists.',
        '_Message must not contain double quotes.',
        '_Message must not contain double quotes.',
    ])]
    public function testInvalidData(
        string $name,
        ?string $warnMessage,
        ?string $postInstMessage,
        ?string $nameExistsMessage,
        ?string $warnMessageInvalidMessage,
        ?string $postInstMessageInvalidMessage,
    ) {
        $package = new Package();
        $package->name = $name;
        $package->warnMessage = $warnMessage;
        $package->postInstMessage = $postInstMessage;
        $package->platform = Platform::Windows;

        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('translate')->willReturnCallback(fn($message) => '_' . $message);

        try {
            $this->createValidator(translator: $translator)->process($package);
            $this->fail('Expected exception was not thrown.');
        } catch (ValidationErrors $errors) {
            $this->assertSame($nameExistsMessage, $errors->nameExistsMessage);
            $this->assertSame($warnMessageInvalidMessage, $errors->warnMessageInvalidMessage);
            $this->assertSame($postInstMessageInvalidMessage, $errors->postInstMessageInvalidMessage);
        }
    }
}
