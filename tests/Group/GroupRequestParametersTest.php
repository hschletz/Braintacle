<?php

namespace Braintacle\Test\Group;

use Braintacle\Group\GroupRequestParameters;
use Braintacle\Group\GroupTransformer;
use Braintacle\Test\DataProcessorTestTrait;
use Model\Group\Group;
use PHPUnit\Framework\TestCase;

class GroupRequestParametersTest extends TestCase
{
    use DataProcessorTestTrait;

    public function testValid()
    {
        $group = $this->createStub(Group::class);

        $groupTransformer = $this->createMock(GroupTransformer::class);
        $groupTransformer->method('transform')->with('groupName')->willReturn($group);

        $dataProcessor = $this->createDataProcessor([GroupTransformer::class => $groupTransformer]);
        $groupRequestParameters = $dataProcessor->process(['name' => 'groupName'], GroupRequestParameters::class);

        $this->assertSame($group, $groupRequestParameters->group);
    }

    public function testGroupMissing()
    {
        $this->assertInvalidFormData(['group' => 'groupName'], GroupRequestParameters::class);
    }
}
