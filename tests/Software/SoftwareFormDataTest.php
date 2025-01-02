<?php

namespace Braintacle\Test\Software;

use Braintacle\Software\Action;
use Braintacle\Software\SoftwareFormData;
use Braintacle\Test\CsrfFormProcessorTestTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SoftwareFormDataTest extends TestCase
{
    use CsrfFormProcessorTestTrait;

    public function testDefaultSoftware()
    {
        $formData = $this->processData(
            ['action' => 'accept'],
            SoftwareFormData::class
        );
        $this->assertEquals([], $formData->software);
    }

    public function testExplicitSoftware()
    {
        $formData = $this->processData(
            [
                'action' => 'accept',
                'software' => ['software1', 'software2'],
            ],
            SoftwareFormData::class
        );
        $this->assertEquals(['software1', 'software2'], $formData->software);
    }

    public static function invalidSoftwareProvider()
    {
        return [
            ['invalid'],
            [['foo' => 'bar']],
            [[42]],
        ];
    }

    #[DataProvider('invalidSoftwareProvider')]
    public function testInvalidSoftware($software)
    {
        $this->assertInvalidFormData(
            [
                'action' => 'accept',
                'software' => $software,
            ],
            SoftwareFormData::class
        );
    }

    public function testAcceptAction()
    {
        $formData = $this->processData(
            ['action' => 'accept'],
            SoftwareFormData::class
        );
        $this->assertEquals(Action::Accept, $formData->action);
    }

    public function testIgnoreAction()
    {
        $formData = $this->processData(
            ['action' => 'ignore'],
            SoftwareFormData::class
        );
        $this->assertEquals(Action::Ignore, $formData->action);
    }

    public function testInvalidAction()
    {
        $this->assertInvalidFormData(
            ['action' => 'invalid'],
            SoftwareFormData::class
        );
    }

    public function testMissingAction()
    {
        $this->assertInvalidFormData(
            [],
            SoftwareFormData::class
        );
    }
}
