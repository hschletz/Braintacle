<?php

namespace Braintacle\Test\Legacy;

use Braintacle\Legacy\Request;
use Laminas\Stdlib\Parameters;
use Laminas\Uri\Http as Uri;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Request::class)]
final class RequestTest extends TestCase
{
    public function testMethodGet()
    {
        $request = new Request();
        $request->setMethod(Request::METHOD_GET);
        $this->assertTrue($request->isGet());
        $this->assertFalse($request->isPost());
    }

    public function testMethodPost()
    {
        $request = new Request();
        $request->setMethod(Request::METHOD_POST);
        $this->assertFalse($request->isGet());
        $this->assertTrue($request->isPost());
    }

    public function testUriObject()
    {
        $uri = new Uri();
        $request = new Request();
        $request->setUri($uri);
        $this->assertSame($uri, $request->getUri());
    }

    public function testUriString()
    {
        $uri = 'http://example.net/';
        $request = new Request();
        $request->setUri($uri);
        $this->assertEquals($uri, $request->getUri()->toString());
    }

    public function testQueryWithoutArguments()
    {
        $request = new Request();
        $this->assertSame([], $request->getQuery()->toArray());

        $query = new Parameters();
        $request->setQuery($query);
        $this->assertSame($query, $request->getQuery());
    }

    public function testQueryWithArguments()
    {
        $query = $this->createMock(Parameters::class);
        $query->method('get')->with('foo', 'bar')->willReturn('foobar');

        $request = new Request();
        $request->setQuery($query);
        $this->assertEquals('foobar', $request->getQuery('foo', 'bar'));
    }

    public function testPostWithoutArguments()
    {
        $request = new Request();
        $this->assertSame([], $request->getPost()->toArray());

        $post = new Parameters();
        $request->setPost($post);
        $this->assertSame($post, $request->getPost());
    }

    public function testPostWithArguments()
    {
        $post = $this->createMock(Parameters::class);
        $post->method('get')->with('foo', null)->willReturn('foobar');

        $request = new Request();
        $request->setPost($post);
        $this->assertEquals('foobar', $request->getPost('foo'));
    }
}
