<?php

namespace Console\Test\Form;

use Console\Form\SoftwareManagementForm;
use Console\Validator\CsrfValidator;
use InvalidArgumentException;
use Laminas\Validator\NotEmpty;
use PHPUnit\Framework\TestCase;

/**
 * Tests for SoftwareManagementForm.
 */
class SoftwareManagementFormTest extends TestCase
{
    private const EXCEPTION_MESSAGE = 'Invalid form data';

    public function testGetValidationMessagesNoCsrf()
    {
        $form = new SoftwareManagementForm();
        $messages = $form->getValidationMessages([]);
        $this->assertTrue(isset($messages['csrf'][NotEmpty::IS_EMPTY]));
    }

    public function testGetValidationMessagesInvalidCsrf()
    {
        $form = new SoftwareManagementForm();
        $messages = $form->getValidationMessages(['csrf' => 'invalid']);
        $this->assertTrue(isset($messages['csrf'][CsrfValidator::NOT_SAME]));
    }

    public function testGetValidationMessagesNoSoftware()
    {
        $form = new SoftwareManagementForm();
        $messages = $form->getValidationMessages(['csrf' => CsrfValidator::getToken(), 'accept' => '']);
        $this->assertEquals([], $messages);
    }

    public function testGetValidationMessagesEmptySoftware()
    {
        $form = new SoftwareManagementForm();
        $messages = $form->getValidationMessages([
            'csrf' => CsrfValidator::getToken(),
            'accept' => '',
            'software' => []
        ]);
        $this->assertEquals([], $messages);
    }

    public function testGetValidationMessagesNonEmptySoftware()
    {
        $form = new SoftwareManagementForm();
        $messages = $form->getValidationMessages([
            'csrf' => CsrfValidator::getToken(),
            'ignore' => '',
            'software' => ['software']
        ]);
        $this->assertEquals([], $messages);
    }

    public function testGetValidationMessagesInvalidSoftware()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(self::EXCEPTION_MESSAGE);
        $form = new SoftwareManagementForm();
        $form->getValidationMessages(['csrf' => CsrfValidator::getToken(), 'accept' => '', 'software' => 'string']);
    }

    public function testGetValidationMessagesNoButtons()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(self::EXCEPTION_MESSAGE);
        $form = new SoftwareManagementForm();
        $form->getValidationMessages(['csrf' => CsrfValidator::getToken()]);
    }

    public function testGetValidationMessagesBothButtons()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(self::EXCEPTION_MESSAGE);
        $form = new SoftwareManagementForm();
        $form->getValidationMessages(['csrf' => CsrfValidator::getToken(), 'accept' => '', 'ignore' => '']);
    }
}
