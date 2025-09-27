<?php

namespace Braintacle\Test\Client\Groups;

use Braintacle\Client\Groups\GroupsTransformer;
use Braintacle\Group\Membership;
use Formotron\AssertionFailedException;
use PHPUnit\Framework\TestCase;
use ValueError;

class GroupsTransformerTest extends TestCase
{
    public function testValid()
    {
        $input = [
            'automatic' => '0',
            'manual' => '1',
            'never' => '2',
        ];
        $output = [
            'automatic' => Membership::Automatic,
            'manual' => Membership::Manual,
            'never' => Membership::Never,
        ];
        $transformer = new GroupsTransformer();
        $this->assertSame($output, $transformer->transform($input, []));
    }

    public function testNotArray()
    {
        $transformer = new GroupsTransformer();
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Expected map, got string');
        $transformer->transform('foo', []);
    }

    public function testNotMap()
    {
        $transformer = new GroupsTransformer();
        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Expected string, got int');
        $transformer->transform(['foo'], []);
    }

    public function testNotEnumValue()
    {
        $transformer = new GroupsTransformer();
        $this->expectException(ValueError::class);
        $transformer->transform(['foo' => '3'], []);
    }
}
