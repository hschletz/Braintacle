<?php

namespace Braintacle\Test\Legacy\Plugin;

use Braintacle\Legacy\Controller;
use Braintacle\Legacy\Plugin\Params;
use Laminas\Http\Request;
use Laminas\Stdlib\Parameters;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Params::class)]
final class ParamsTest extends TestCase
{
    private function createParams(Request $request): Params
    {
        $controller = $this->createStub(Controller::class);
        $controller->method('getRequest')->willReturn($request);

        $params = new Params();
        $params->setController($controller);

        return $params;
    }

    public function testController()
    {
        $controller = $this->createStub(Controller::class);
        $params = new Params();
        $params->setController($controller);
        $this->assertSame($controller, $params->getController());
    }

    public function testInvoke()
    {
        $params = new Params();
        $this->assertSame($params, $params());
    }

    public function testFromQuery()
    {
        $request = new Request();
        $request->setQuery(new Parameters(['foo' => 'bar']));

        $params = $this->createParams($request);

        $this->assertEquals(['foo' => 'bar'], $params->fromQuery());
        $this->assertEquals('bar', $params->fromQuery('foo'));
        $this->assertEquals('baz', $params->fromQuery('foobar', 'baz'));
        $this->assertNull($params->fromQuery('foobar'));
    }

    public function testFromPost()
    {
        $request = new Request();
        $request->setPost(new Parameters(['foo' => 'bar']));

        $params = $this->createParams($request);

        $this->assertEquals(['foo' => 'bar'], $params->fromPost());
        $this->assertEquals('bar', $params->fromPost('foo'));
        $this->assertNull($params->fromPost('foobar'));
    }

    public function testFromFiles()
    {
        $request = new Request();
        $request->setFiles(new Parameters(['foo' => 'bar']));

        $params = $this->createParams($request);

        $this->assertEquals(['foo' => 'bar'], $params->fromFiles());
    }
}
