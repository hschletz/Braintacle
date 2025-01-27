<?php

namespace Braintacle\Test\Client\Groups;

use Braintacle\Client\Groups\GroupsTransformer;
use Braintacle\Client\Groups\MembershipsFormData;
use Braintacle\Test\CsrfFormProcessorTestTrait;
use PHPUnit\Framework\TestCase;

class MembershipsFormDataTest extends TestCase
{
    use CsrfFormProcessorTestTrait;

    public function testValid()
    {
        $groupData = ['foo' => 2];

        $transformer = $this->createStub(GroupsTransformer::class);
        $transformer->method('transform')->willReturn($groupData);

        $formData = $this->processData(
            ['groups' => []],
            MembershipsFormData::class,
            [GroupsTransformer::class => $transformer]
        );
        $this->assertSame($groupData, $formData->groups);
    }

    public function testInvalid()
    {
        $transformer = $this->createStub(GroupsTransformer::class);
        $transformer->method('transform')->willReturn('');

        $this->assertInvalidFormData(
            ['groups' => []],
            MembershipsFormData::class,
            [GroupsTransformer::class => $transformer]
        );
    }
}
