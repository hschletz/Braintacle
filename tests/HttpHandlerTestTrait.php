<?php

namespace Braintacle\Test;

use Braintacle\CsrfProcessor;
use DI\Container;
use DOMXPath;
use Formotron\DataProcessor;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Uri;
use Masterminds\HTML5;
use PHPUnit\Framework\Attributes\Before;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

/**
 * Utility trait for testing PSR-15 request handlers.
 */
trait HttpHandlerTestTrait
{
    private ServerRequestInterface $request;
    private ResponseInterface $response;
    private UriInterface $uri;

    #[Before]
    public function setupMessageObjects()
    {
        $this->request = new ServerRequest('GET', '');
        $this->response = new Response();
        $this->uri = new Uri('https://example.com/test');
    }

    /**
     * Create DataProcessor with the CsrfProcessor stubbed out.
     */
    private function createDataProcessor(): DataProcessor
    {
        $csrfProcessor = $this->createStub(CsrfProcessor::class);
        $csrfProcessor->method('process')->willReturnArgument(0);
        $container = new Container([CsrfProcessor::class => $csrfProcessor]);
        $dataProcessor = $container->get(DataProcessor::class);

        return $dataProcessor;
    }

    private function getMessageContent(MessageInterface $message): string
    {
        $body = $message->getBody();
        $body->rewind();

        return $body->getContents();
    }

    private function getXPathFromMessage(MessageInterface $message): DOMXPath
    {
        $html = new HTML5(['disable_html_ns' => true]);
        $document = $html->loadHTML($this->getMessageContent($message));
        $this->assertFalse($html->hasErrors());

        return new DOMXPath($document);
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
        $this->assertEquals($content, $this->getMessageContent($response));
    }
}
