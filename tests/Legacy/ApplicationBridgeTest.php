<?php

namespace Braintacle\Test\Legacy;

use Braintacle\Legacy\ApplicationBridge;
use Braintacle\Legacy\MvcApplication;
use Braintacle\Legacy\MvcEvent;
use Braintacle\Template\TemplateEngine;
use Braintacle\Test\HttpHandlerTestTrait;
use Laminas\Http\Response as MvcResponse;
use PHPUnit\Framework\TestCase;

class ApplicationBridgeTest extends TestCase
{
    use HttpHandlerTestTrait;

    public function testResponseHeaders()
    {
        $mvcResponse = new MvcResponse();
        $mvcResponse->setStatusCode(418);
        $mvcResponse->getHeaders()->addHeaders([
            'X-Header1: header1',
            'X-Header2: header2a',
            'X-Header2: header2b',
        ]);

        $mvcEvent = new MvcEvent();
        $mvcEvent->setResponse($mvcResponse);

        $mvcApplication = $this->createStub(MvcApplication::class);
        $mvcApplication->method('run')->willReturn($mvcEvent);

        $templateEngine = $this->createStub(TemplateEngine::class);

        $applicationBridge = new ApplicationBridge($this->response, $mvcApplication, $templateEngine);
        $response = $applicationBridge->handle($this->request);

        $this->assertResponseStatusCode(418, $response);
        $this->assertResponseHeaders([
            'X-Header1' => ['header1'],
            'X-Header2' => ['header2a', 'header2b'],
        ], $response);
    }

    public function testResponseContentWithoutTemplate()
    {
        $mvcResponse = new MvcResponse();
        $mvcResponse->setContent('mvc_content');

        $mvcEvent = new MvcEvent();
        $mvcEvent->setResponse($mvcResponse);

        $mvcApplication = $this->createStub(MvcApplication::class);
        $mvcApplication->method('run')->willReturn($mvcEvent);

        $templateEngine = $this->createStub(TemplateEngine::class);

        $applicationBridge = new ApplicationBridge($this->response, $mvcApplication, $templateEngine);
        $response = $applicationBridge->handle($this->request);

        $this->assertResponseContent('mvc_content', $response);
    }

    public function testResponseContentWithTemplate()
    {
        $mvcResponse = new MvcResponse();
        $mvcResponse->setContent('mvc_content');

        $mvcEvent = new MvcEvent();
        $mvcEvent->setResponse($mvcResponse);
        $mvcEvent->setParam('template', 'layout_template');
        $mvcEvent->setParam('subMenuRoute', 'sub_menu_route');

        $mvcApplication = $this->createStub(MvcApplication::class);
        $mvcApplication->method('run')->willReturn($mvcEvent);

        $templateEngine = $this->createMock(TemplateEngine::class);
        $templateEngine->method('render')->with(
            'layout_template',
            ['content' => 'mvc_content', 'subMenuRoute' => 'sub_menu_route'],
        )->willReturn('template_content');

        $applicationBridge = new ApplicationBridge($this->response, $mvcApplication, $templateEngine);
        $response = $applicationBridge->handle($this->request);

        $this->assertResponseContent('template_content', $response);
    }
}
