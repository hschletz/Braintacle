<?php

namespace Braintacle\Test\Group;

use Braintacle\Group\Group;
use Braintacle\Group\Groups;
use Braintacle\Group\GroupTransformer;
use Formotron\Transformer;
use PHPUnit\Framework\TestCase;

class GroupTransformerTest extends TestCase
{
    public function testTransform()
    {
        $group = $this->createStub(Group::class);

        $groups = $this->createMock(Groups::class);
        $groups->method('getGroup')->with('groupName')->willReturn($group);

        $groupTransformer = new GroupTransformer($groups);
        $this->assertInstanceOf(Transformer::class, $groupTransformer);
        $this->assertSame($group, $groupTransformer->transform('groupName', []));
    }
}
