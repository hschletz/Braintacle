<?php

namespace Braintacle\Test\Legacy;

use Braintacle\Legacy\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Response::class)]
final class ResponseTest extends TestCase
{
    public function testStatusCode()
    {
        $response = new Response();
        $this->assertEquals(200, $response->getStatusCode());
        $response->setStatusCode(418);
        $this->assertEquals(418, $response->getStatusCode());
    }

    public function testHeaders()
    {
        $response = new Response();
        $this->assertSame([], $response->getHeaders());
        $response->setHeader('foo', 'bar');
        $response->setHeader('baz', 'baz1');
        $response->setHeader('baz', 'baz2');
        $this->assertEquals(['foo' => 'bar', 'baz' => 'baz2'], $response->getHeaders());
    }

    public function testContent()
    {
        $response = new Response();
        $this->assertSame('', $response->getContent());
        $response->setContent('content');
        $this->assertEquals('content', $response->getContent());
        $response->setContent(null);
        $this->assertSame('', $response->getContent());
    }
}
