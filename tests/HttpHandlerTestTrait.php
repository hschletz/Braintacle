<?php

namespace Braintacle\Test;

use Braintacle\CsrfProcessor;
use Braintacle\Template\Function\AssetUrlFunction;
use Braintacle\Template\Function\CsrfTokenFunction;
use Braintacle\Template\Function\PathForRouteFunction;
use Braintacle\Template\TemplateEngine;
use Composer\InstalledVersions;
use Console\Template\Functions\TranslateFunction;
use Console\Template\TemplateLoader;
use DI\Container;
use DOMXPath;
use Formotron\FormProcessor;
use Latte\Engine;
use Masterminds\HTML5;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use Nyholm\Psr7\Uri;
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
     * Create FormProcessor with the CsrfProcessor stupped out.
     */
    private function createFormProcessor(): FormProcessor
    {
        $csrfProcessor = $this->createStub(CsrfProcessor::class);
        $csrfProcessor->method('process')->willReturnArgument(0);
        $container = new Container([CsrfProcessor::class => $csrfProcessor]);
        $formProcessor = $container->get(FormProcessor::class);

        return $formProcessor;
    }

    private function createTemplateEngine(array $templateFunctions = []): TemplateEngine
    {
        return new TemplateEngine(
            new Engine(),
            new TemplateLoader(InstalledVersions::getRootPackage()['install_path'] . 'templates'),
            $templateFunctions[AssetUrlFunction::class] ?? $this->createStub(AssetUrlFunction::class),
            $templateFunctions[CsrfTokenFunction::class] ?? $this->createStub(CsrfTokenFunction::class),
            $templateFunctions[PathForRouteFunction::class] ?? $this->createStub(PathForRouteFunction::class),
            $templateFunctions[TranslateFunction::class] ?? $this->createStub(TranslateFunction::class),
        );
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

    private function assertResponseContentMatches(string $regex, ResponseInterface $response)
    {
        $this->assertMatchesRegularExpression($regex, $this->getMessageContent($response));
    }
}
