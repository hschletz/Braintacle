<?php

namespace Library\Test;

class TranslatorTest extends \PHPUnit\Framework\TestCase
{
    protected static $_defaultLocale;

    public static function setUpBeforeClass(): void
    {
        // Preserve global state
        static::$_defaultLocale = \Locale::getDefault();
    }

    public function tearDown(): void
    {
        // Reset after every test to avoid interference
        \Locale::setDefault(static::$_defaultLocale);
    }

    public static function translatorSetupProvider()
    {
        return array(
            // Messages from Library module
            array('en', "File '%value%' is not readable", "File '%value%' is not readable"),
            array('en_UK', "File '%value%' is not readable", "File '%value%' is not readable"),
            array('de', "File '%value%' is not readable", "Datei '%value%' ist nicht lesbar"),
            array('de_DE', "File '%value%' is not readable", "Datei '%value%' ist nicht lesbar"),
            // Messages from Laminas resources
            array('en', "Value is required and can't be empty", "Value is required and can't be empty"),
            array('en_UK', "Value is required and can't be empty", "Value is required and can't be empty"),
            array('de', "Value is required and can't be empty", 'Es wird eine Eingabe benötigt'),
            array('de_DE', "Value is required and can't be empty", 'Es wird eine Eingabe benötigt'),
        );
    }

    /**
     * @dataProvider translatorSetupProvider
     */
    public function testTranslatorSetup($locale, $message, $expectedMessage)
    {
        \Locale::setDefault($locale);
        $serviceManager = \Library\Application::init('Library')->getServiceManager();
        $serviceManager->setService('Library\UserConfig', array());
        $translator = $serviceManager->get('MvcTranslator');
        $this->assertEquals($expectedMessage, $translator->translate($message));
    }

    public static function missingTranslationProvider()
    {
        return array(
            array('de'),
            array('de_DE'),
        );
    }

    /**
     * @dataProvider missingTranslationProvider
     */
    public function testMissingTranslationTriggersNoticeWhenEnabled($locale)
    {
        \Locale::setDefault($locale);
        $serviceManager = \Library\Application::init('Library')->getServiceManager();
        $serviceManager->setService(
            'Library\UserConfig',
            array(
                'debug' => array('report missing translations' => true),
            )
        );
        $translator = $serviceManager->get('MvcTranslator');

        $errorMessage = null;
        $errorHandler = set_error_handler(
            function (int $errno, string $errstr, string $errfile, int $errline, array $errcontext = []) use (&$errorMessage) {
                $errorMessage = $errstr;
                return true;
            },
            E_USER_NOTICE
        );
        $translator->translate('this_string_is_not_translated');
        set_error_handler($errorHandler);
        $this->assertEquals('Missing translation: this_string_is_not_translated', $errorMessage);
    }

    /**
     * @dataProvider missingTranslationProvider
     */
    public function testMissingTranslationDoesNotTriggerNoticeWhenDisabled($locale)
    {
        \Locale::setDefault($locale);
        $serviceManager = \Library\Application::init('Library')->getServiceManager();
        $serviceManager->setService(
            'Library\UserConfig',
            array(
                'debug' => array('report missing translations' => false),
            )
        );
        $translator = $serviceManager->get('MvcTranslator');
        $this->assertEquals('this_string_is_not_translated', $translator->translate('this_string_is_not_translated'));
    }

    /**
     * @dataProvider missingTranslationProvider
     */
    public function testMissingTranslationDoesNotTriggerNoticebyDefault($locale)
    {
        \Locale::setDefault($locale);
        $serviceManager = \Library\Application::init('Library')->getServiceManager();
        $serviceManager->setService('Library\UserConfig', array());
        $translator = $serviceManager->get('MvcTranslator');
        $this->assertEquals('this_string_is_not_translated', $translator->translate('this_string_is_not_translated'));
    }
}
