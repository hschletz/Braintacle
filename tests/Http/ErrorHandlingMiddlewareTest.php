<?php

namespace Braintacle\Test\Http;

use Braintacle\AppConfig;
use Braintacle\Http\ErrorHandlingMiddleware;
use Braintacle\Test\HttpHandlerTestTrait;
use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpException;

class ErrorHandlingMiddlewareTest extends TestCase
{
    use HttpHandlerTestTrait;

    public function testNoError()
    {
        $appConfig = $this->createStub(AppConfig::class);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('error');

        $middleware = new ErrorHandlingMiddleware($this->response, $appConfig, $logger);

        $expectedResponse = $this->createStub(ResponseInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')->with($this->request)->willReturn($expectedResponse);

        $returnedResponse = $middleware->process($this->request, $handler);
        $this->assertSame($expectedResponse, $returnedResponse);
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

        $exception = new Exception('>message', 418); // code should be ignored
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error')->with('Braintacle error', ['exception' => $exception]);

        $middleware = new ErrorHandlingMiddleware($this->response, $appConfig, $logger);

        $handler = $this->createStub(RequestHandlerInterface::class);
        $handler->method('handle')->willThrowException($exception);

        $response = $middleware->process($this->request, $handler);
        $this->assertResponseStatusCode(500, $response); // default code
        $this->assertResponseContent('&gt;message', $response);
    }

    #[DataProvider('noBacktraceProvider')]
    public function testHttpExceptionNoBacktrace(array $debugOptions)
    {
        $appConfig = $this->createMock(AppConfig::class);
        $appConfig->method('__get')->with('debug')->willReturn($debugOptions);

        $logger = $this->createStub(LoggerInterface::class);

        $middleware = new ErrorHandlingMiddleware($this->response, $appConfig, $logger);

        $handler = $this->createStub(RequestHandlerInterface::class);
        $handler->method('handle')->willThrowException(new HttpException($this->request, '>message', 418));

        $response = $middleware->process($this->request, $handler);
        $this->assertResponseStatusCode(418, $response);
        $this->assertResponseContent('&gt;message', $response);
    }

    public function testBacktrace()
    {
        $appConfig = $this->createMock(AppConfig::class);
        $appConfig->method('__get')->with('debug')->willReturn(['display backtrace' => true]);

        $logger = $this->createStub(LoggerInterface::class);

        $middleware = new ErrorHandlingMiddleware($this->response, $appConfig, $logger);

        $handler = $this->createStub(RequestHandlerInterface::class);
        $handler->method('handle')->willThrowException(new Exception('message'));

        $response = $middleware->process($this->request, $handler);
        $this->assertResponseContentMatches('/^message.+/', $response);
    }
}
