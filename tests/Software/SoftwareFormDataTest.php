<?php

namespace Braintacle\Test\Software;

use Braintacle\Software\Action;
use Braintacle\Software\SoftwareFormData;
use Braintacle\Test\FormProcessorTestTrait;
use Formotron\AssertionFailedException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SoftwareFormDataTest extends TestCase
{
    use FormProcessorTestTrait;

    public function testDefaultSoftware()
    {
        $formData = $this->createFormProcessor()->process(
            ['action' => 'accept'],
            SoftwareFormData::class
        );
        $this->assertEquals([], $formData->software);
    }

    public function testExplicitSoftware()
    {
        $formData = $this->createFormProcessor()->process(
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
        $this->expectException(AssertionFailedException::class);
        $this->createFormProcessor()->process(
            [
                'action' => 'accept',
                'software' => $software,
            ],
            SoftwareFormData::class
        );
    }

    public function testAcceptAction()
    {
        $formData = $this->createFormProcessor()->process(
            ['action' => 'accept'],
            SoftwareFormData::class
        );
        $this->assertEquals(Action::Accept, $formData->action);
    }

    public function testIgnoreAction()
    {
        $formData = $this->createFormProcessor()->process(
            ['action' => 'ignore'],
            SoftwareFormData::class
        );
        $this->assertEquals(Action::Ignore, $formData->action);
    }

    public function testInvalidAction()
    {
        $this->expectException(AssertionFailedException::class);
        $this->createFormProcessor()->process(
            ['action' => 'invalid'],
            SoftwareFormData::class
        );
    }

    public function testMissingAction()
    {
        $this->expectException(AssertionFailedException::class);
        $this->createFormProcessor()->process(
            [],
            SoftwareFormData::class
        );
    }
}
