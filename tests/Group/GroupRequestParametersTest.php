<?php

namespace Braintacle\Test\Group;

use Braintacle\Group\GroupRequestParameters;
use Braintacle\Group\GroupTransformer;
use Braintacle\Test\FormProcessorTestTrait;
use Model\Group\Group;
use PHPUnit\Framework\TestCase;

class GroupRequestParametersTest extends TestCase
{
    use FormProcessorTestTrait;

    public function testValid()
    {
        $group = $this->createStub(Group::class);

        $groupTransformer = $this->createMock(GroupTransformer::class);
        $groupTransformer->method('transform')->with('groupName')->willReturn($group);

        $formProcessor = $this->createFormProcessor([GroupTransformer::class => $groupTransformer]);
        $groupRequestParameters = $formProcessor->process(['name' => 'groupName'], GroupRequestParameters::class);

        $this->assertSame($group, $groupRequestParameters->group);
    }

    public function testGroupMissing()
    {
        $this->assertInvalidFormData(['group' => 'groupName'], GroupRequestParameters::class);
    }
}
