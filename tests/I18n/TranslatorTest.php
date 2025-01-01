<?php

namespace Braintacle\Test\I18n;

use Braintacle\AppConfig;
use Braintacle\I18n\Translator;
use Composer\InstalledVersions;
use LogicException;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\BeforeClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class TranslatorTest extends TestCase
{
    private static string $i18nPath;

    #[BeforeClass]
    public static function setI18nPath()
    {
        self::$i18nPath = InstalledVersions::getRootPackage()['install_path'] . '/i18n';
    }

    public function testGetTranslations()
    {
        $translator = new Translator('de', self::$i18nPath, $this->createStub(AppConfig::class));
        $translations = $translator->getTranslations();

        $this->assertEquals('Beschreibung', $translations['Description']); // extracted
        $this->assertEquals("'%value%' ist in der Liste ungültiger Werte", $translations["'%value%' is in the list of invalid values"]); // manual
        $this->assertEquals('Ungültige Eingabe.', $translations['Invalid type given']); // Laminas Resources
    }

    public function testMissingLanguage()
    {
        $translator = new Translator('missing', self::$i18nPath, $this->createStub(AppConfig::class));
        $this->assertEquals([], $translator->getTranslations());
    }

    public function testFuzzyShouldBeIgnored()
    {
        $vfsRoot = vfsStream::setup();
        vfsStream::newFile('de.po')->at($vfsRoot)->withContent('
            msgid "_non_fuzzy_"
            msgstr ""
            #, fuzzy
            msgid "_fuzzy_"
            msgstr ""
        ');

        $translator = new Translator('de', $vfsRoot->url(), $this->createStub(AppConfig::class));
        $translations = $translator->getTranslations();

        $this->assertArrayHasKey('_non_fuzzy_', $translations);
        $this->assertArrayNotHasKey('_fuzzy_', $translations);
    }

    public function testTranslateTranslated()
    {
        $translator = new Translator('de', self::$i18nPath, $this->createStub(AppConfig::class));
        $this->assertEquals('Beschreibung', $translator->translate('Description'));
    }

    public function testTranslateLanguageNotAvailable()
    {
        $translator = new Translator('en', self::$i18nPath, $this->createStub(AppConfig::class));
        $this->assertEquals('Description', $translator->translate('Description'));
    }

    public function testTranslateTranslationMissingExceptionDisabled()
    {
        $appConfig = $this->createMock(AppConfig::class);
        $appConfig->expects($this->once())->method('__get')->with('debug')->willReturn([]);

        $translator = new Translator('de', self::$i18nPath, $appConfig);
        $this->assertEquals('_missing_', $translator->translate('_missing_'));
    }

    public function testTranslateTranslationMissingExceptionThrown()
    {
        $appConfig = $this->createMock(AppConfig::class);
        $appConfig->expects($this->once())->method('__get')->with('debug')->willReturn(['report missing translations' => true]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Missing translation: _missing_');

        $translator = new Translator('de', self::$i18nPath, $appConfig);
        $this->assertEquals('_missing_', $translator->translate('_missing_'));
    }

    public function testTranslatePlural()
    {
        $translator = new Translator('en', self::$i18nPath, $this->createStub(AppConfig::class));

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('translatePlural() is not implemented yet.');

        $translator->translatePlural('', '', 0);
    }
}
