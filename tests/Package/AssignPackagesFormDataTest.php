<?php

namespace Braintacle\Test\Package;

use Braintacle\Package\AssignPackagesFormData;
use Braintacle\Test\CsrfFormProcessorTestTrait;
use Braintacle\Validator\IsStringList;
use PHPUnit\Framework\TestCase;

class AssignPackagesFormDataTest extends TestCase
{
    use CsrfFormProcessorTestTrait;

    public function testPackages()
    {
        $packages = ['package1', 'package2'];

        $isStringList = $this->createMock(IsStringList::class);
        $isStringList->expects($this->once())->method('getValidationErrors')->with($packages)->willReturn([]);

        $dataProcessor = $this->createDataProcessor([IsStringList::class => $isStringList]);
        $formData = $dataProcessor->process(['packages' => $packages], AssignPackagesFormData::class);

        $this->assertEquals($packages, $formData->packageNames);
    }

    public function testNoPackages()
    {
        $isStringList = $this->createMock(IsStringList::class);
        $isStringList->expects($this->once())->method('getValidationErrors')->with([])->willReturn([]);

        $dataProcessor = $this->createDataProcessor([IsStringList::class => $isStringList]);
        $formData = $dataProcessor->process([], AssignPackagesFormData::class);

        $this->assertEquals([], $formData->packageNames);
    }
}
