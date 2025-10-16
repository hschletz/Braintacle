<?php

namespace Braintacle\Test\Group;

use AssertionError;
use Braintacle\Group\CacheExpirationTransformer;
use DateTimeInterface;
use Model\Config;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CacheExpirationTransformer::class)]
final class CacheExpirationTransformerTest extends TestCase
{
    private function createTransformer(): CacheExpirationTransformer
    {
        $config = $this->createMock(Config::class);
        $config->method('__get')->with('groupCacheExpirationInterval')->willReturn(30);

        return new CacheExpirationTransformer($config);
    }

    public function testArguments()
    {
        $this->expectException(AssertionError::class);
        $transformer = $this->createTransformer();
        $transformer->transform(0, ['arg']);
    }

    public function testStringInput()
    {
        $this->expectException(AssertionError::class);
        $transformer = $this->createTransformer();
        $transformer->transform('0', []);
    }

    public function testNullInput()
    {
        $this->expectException(AssertionError::class);
        $transformer = $this->createTransformer();
        $transformer->transform(null, []);
    }

    public function testZeroInput()
    {
        $transformer = $this->createTransformer();
        $this->assertNull($transformer->transform(0, []));
    }

    public function testNonZeroInput()
    {
        $transformer = $this->createTransformer();
        $transformedValue = $transformer->transform(1000, []);
        assert($transformedValue instanceof DateTimeInterface);
        $this->assertEquals(1030, $transformedValue->getTimestamp());
    }
}
