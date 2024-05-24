<?php

namespace Braintacle\Test;

use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\Before;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Utility trait for testing PSR-15 request handlers.
 */
trait HttpHandlerTestTrait
{
    private ServerRequestInterface $request;
    private ResponseInterface $response;

    #[Before]
    public function setupMessageObjects()
    {
        $this->request = new ServerRequest('GET', '');
        $this->response = new Response();
    }

    private function assertResponseStatusCode(int $statusCode, ResponseInterface $response)
    {
        $this->assertEquals($statusCode, $response->getStatusCode());
    }

    private function assertResponseHeaders(array $headers, ResponseInterface $response)
    {
        $this->assertEquals($headers, $response->getHeaders());
    }

    private function assertResponseContent(string $content, ResponseInterface $response)
    {
        $body = $response->getBody();
        $body->rewind();
        $this->assertEquals($content, $body->getContents());
    }

    private function assertResponseContentMatches(string $regex, ResponseInterface $response)
    {
        $body = $response->getBody();
        $body->rewind();
        $this->assertMatchesRegularExpression($regex, $body->getContents());
    }
}
