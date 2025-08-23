<?php

namespace Braintacle\Test\Package;

use Braintacle\Package\AssignPackagesFormData;
use Braintacle\Test\CsrfFormProcessorTestTrait;
use Braintacle\Validator\IsStringList;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AssignPackagesFormData::class)]
#[UsesClass(IsStringList::class)]
class AssignPackagesFormDataTest extends TestCase
{
    use CsrfFormProcessorTestTrait;

    public function testPackages()
    {
        $packages = ['package1', 'package2'];

        $dataProcessor = $this->createDataProcessor();
        $formData = $dataProcessor->process(['packages' => $packages], AssignPackagesFormData::class);

        $this->assertEquals($packages, $formData->packageNames);
    }

    public function testNoPackages()
    {
        $dataProcessor = $this->createDataProcessor();
        $formData = $dataProcessor->process([], AssignPackagesFormData::class);

        $this->assertEquals([], $formData->packageNames);
    }

    public function testInvalidPackages()
    {
        $this->expectException(InvalidArgumentException::class);
        $dataProcessor = $this->createDataProcessor();
        $dataProcessor->process(['packages' => [42]], AssignPackagesFormData::class);
    }
}
