<?php

namespace Braintacle\Test\Group\Packages;

use Braintacle\Group\GroupTransformer;
use Braintacle\Group\Packages\RemovePackagesParameters;
use Braintacle\Test\FormProcessorTestTrait;
use Model\Group\Group;
use PHPUnit\Framework\TestCase;

class RemovePackagesParametersTest extends TestCase
{
    use FormProcessorTestTrait;

    public function testValid()
    {
        $group = $this->createStub(Group::class);

        $groupTransformer = $this->createMock(GroupTransformer::class);
        $groupTransformer->method('transform')->with('groupName')->willReturn($group);

        $formProcessor = $this->createFormProcessor([GroupTransformer::class => $groupTransformer]);
        $removePackageParameters = $formProcessor->process([
            'name' => 'groupName',
            'package' => 'packageName',
        ], RemovePackagesParameters::class);

        $this->assertSame($group, $removePackageParameters->group);
        $this->assertEquals('packageName', $removePackageParameters->packageName);
    }

    public function testGroupMissing()
    {
        $this->assertInvalidFormData([
            'group' => 'groupName',
            'package' => 'packageName',
        ], RemovePackagesParameters::class);
    }

    public function testPackageMissing()
    {
        $groupTransformer = $this->createStub(GroupTransformer::class);
        $groupTransformer->method('transform')->willReturn($this->createStub(Group::class));

        $this->assertInvalidFormData([
            'name' => 'groupName',
            'packageName' => 'packageName',
        ], RemovePackagesParameters::class, [GroupTransformer::class => $groupTransformer]);
    }
}
