<?php

namespace Braintacle\Test\Group;

use Braintacle\Group\Group;
use Braintacle\Group\GroupTransformer;
use Formotron\Transformer;
use Model\Group\GroupManager;
use PHPUnit\Framework\TestCase;

class GroupTransformerTest extends TestCase
{
    public function testTransform()
    {
        $group = $this->createStub(Group::class);

        $groupManager = $this->createMock(GroupManager::class);
        $groupManager->method('getGroup')->with('groupName')->willReturn($group);

        $groupTransformer = new GroupTransformer($groupManager);
        $this->assertInstanceOf(Transformer::class, $groupTransformer);
        $this->assertSame($group, $groupTransformer->transform('groupName', []));
    }
}
