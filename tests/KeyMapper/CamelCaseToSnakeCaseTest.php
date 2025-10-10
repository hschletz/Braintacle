<?php

namespace Braintacle\Test\KeyMapper;

use Braintacle\KeyMapper\CamelCaseToSnakeCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CamelCaseToSnakeCase::class)]
final class CamelCaseToSnakeCaseTest extends TestCase
{
    public function testGetKey()
    {
        $mapper = new CamelCaseToSnakeCase();
        $this->assertEquals('foo_bar', $mapper->getKey('fooBar'));
    }
}
