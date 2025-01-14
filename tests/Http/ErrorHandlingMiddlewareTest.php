<?php

namespace Braintacle\Test\Http;

use Braintacle\AppConfig;
use Braintacle\Http\ErrorHandlingMiddleware;
use Braintacle\Template\TemplateEngine;
use Braintacle\Template\TemplateLoader;
use Braintacle\Test\HttpHandlerTestTrait;
use Braintacle\Test\TemplateTestTrait;
use Exception;
use Nyholm\Psr7\ServerRequest;
use Nyholm\Psr7\UploadedFile;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpException;

#[CoversClass(ErrorHandlingMiddleware::class)]
#[UsesClass(TemplateEngine::class)]
#[UsesClass(TemplateLoader::class)]
class ErrorHandlingMiddlewareTest extends TestCase
{
    use HttpHandlerTestTrait;
    use TemplateTestTrait;

    public function testNoError()
    {
        $appConfig = $this->createStub(AppConfig::class);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('error');

        $templateEngine = $this->createStub(TemplateEngine::class);

        $middleware = new ErrorHandlingMiddleware($this->response, $appConfig, $logger, $templateEngine);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')->with($this->request)->willReturn($this->response);

        $response = $middleware->process($this->request, $handler);
        $this->assertSame($this->response, $response);
    }

    public static function noBacktraceProvider()
    {
        return [
            [[]],
            [['display backtrace' => false]],
        ];
    }

    #[DataProvider('noBacktraceProvider')]
    public function testGenericExceptionNoBacktrace(array $debugOptions)
    {
        $appConfig = $this->createMock(AppConfig::class);
        $appConfig->method('__get')->with('debug')->willReturn($debugOptions);

        $exception = new Exception('exception message', 418); // code should be ignored
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error')->with('', ['exception' => $exception]);

        $templateEngine = $this->createTemplateEngine();

        $middleware = new ErrorHandlingMiddleware($this->response, $appConfig, $logger, $templateEngine);

        $handler = $this->createStub(RequestHandlerInterface::class);
        $handler->method('handle')->willThrowException($exception);

        $response = $middleware->process($this->request, $handler);
        $this->assertResponseStatusCode(500, $response); // default code

        $xPath = $this->getXPathFromMessage($response);
        $this->assertCount(1, $xPath->query('//h2[text()="exception message"]'));
        $this->assertCount(0, $xPath->query('//pre'));
        $this->assertCount(1, $xPath->query('//p[text()="See web server error log for details."]'));
    }

    #[DataProvider('noBacktraceProvider')]
    public function testHttpExceptionNoBacktrace(array $debugOptions)
    {
        $appConfig = $this->createMock(AppConfig::class);
        $appConfig->method('__get')->with('debug')->willReturn($debugOptions);

        $logger = $this->createStub(LoggerInterface::class);
        $templateEngine = $this->createTemplateEngine();

        $middleware = new ErrorHandlingMiddleware($this->response, $appConfig, $logger, $templateEngine);

        $handler = $this->createStub(RequestHandlerInterface::class);
        $handler->method('handle')->willThrowException(new HttpException($this->request, 'exception message', 418));

        $response = $middleware->process($this->request, $handler);
        $this->assertResponseStatusCode(418, $response);

        $xPath = $this->getXPathFromMessage($response);
        $this->assertCount(1, $xPath->query('//h2[text()="exception message"]'));
        $this->assertCount(0, $xPath->query('//pre'));
        $this->assertCount(1, $xPath->query('//p[text()="See web server error log for details."]'));
    }

    public function testDebugInfo()
    {
        $appConfig = $this->createMock(AppConfig::class);
        $appConfig->method('__get')->with('debug')->willReturn(['display backtrace' => true]);

        $logger = $this->createStub(LoggerInterface::class);
        $templateEngine = $this->createTemplateEngine();

        $middleware = new ErrorHandlingMiddleware($this->response, $appConfig, $logger, $templateEngine);

        $handler = $this->createStub(RequestHandlerInterface::class);
        $handler->method('handle')->willThrowException(new Exception('exception message'));

        $request = (new ServerRequest('POST', '?foo=bar', serverParams: ['param' => 'value']))
            ->withParsedBody(['bar' => 'baz'])
            ->withUploadedFiles([new UploadedFile('file', 42, UPLOAD_ERR_OK, 'filename', 'mediatype')]);
        $response = $middleware->process($request, $handler);
        $xPath = $this->getXPathFromMessage($response);

        $this->assertCount(1, $xPath->query('//pre[contains(text(), "exception message")]'));
        $this->assertEquals('POST', $xPath->evaluate('string(//h4[text()="Method"]/following-sibling::p[1])'));

        $this->assertStringContainsString(
            '"foo" => "bar"',
            $xPath->evaluate('string(//h4[text()="URL parameters"]/following-sibling::pre[1])')
        );

        $this->assertStringContainsString(
            '"bar" => "baz"',
            $xPath->evaluate('string(//h4[text()="POST parameters"]/following-sibling::pre[1])')
        );

        $uploadedFiles = $xPath->evaluate('string(//h4[text()="Files"]/following-sibling::pre[1])');
        $this->assertStringContainsString('"clientFilename" => "filename"', $uploadedFiles);
        $this->assertStringContainsString('"clientMediaType" => "mediatype"', $uploadedFiles);
        $this->assertStringContainsString('"size" => 42', $uploadedFiles);
        $this->assertStringContainsString('"error" => 0', $uploadedFiles);

        $this->assertStringContainsString(
            '"param" => "value"',
            $xPath->evaluate('string(//h4[text()="Server variables"]/following-sibling::pre[1])')
        );
    }

    public function testDebugInfoNoPostData()
    {
        $appConfig = $this->createMock(AppConfig::class);
        $appConfig->method('__get')->with('debug')->willReturn(['display backtrace' => true]);

        $logger = $this->createStub(LoggerInterface::class);
        $templateEngine = $this->createTemplateEngine();

        $middleware = new ErrorHandlingMiddleware($this->response, $appConfig, $logger, $templateEngine);

        $handler = $this->createStub(RequestHandlerInterface::class);
        $handler->method('handle')->willThrowException(new Exception('exception message'));

        $response = $middleware->process($this->request, $handler);
        $xPath = $this->getXPathFromMessage($response);

        $this->assertEquals(
            '[]',
            trim($xPath->evaluate('string(//h4[text()="POST parameters"]/following-sibling::pre[1])'))
        );
    }
}
