<?php

namespace Braintacle\Test\Client\Import;

use Braintacle\Client\Import\Importer;
use Braintacle\Client\Import\ImportError;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Utils;
use Model\Config;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;

#[CoversClass(Importer::class)]
final class ImporterTest extends TestCase
{
    public function testImportStreamSuccess()
    {
        $config = $this->createMock(Config::class);
        $config->method('__get')->with('communicationServerUri')->willReturn('communication_server');

        /** @var array{request: RequestInterface}[] */
        $history = [];
        $handlerStack = HandlerStack::create(new MockHandler([new Response(200)]));
        $handlerStack->push(Middleware::history($history));
        $httpClient = new HttpClient(['handler' => $handlerStack]);

        $importer = new Importer($config, $httpClient);
        /** @psalm-suppress UndefinedFunction (Psalm bug, would complain about nonexisten function _content()) */
        $importer->importStream(Utils::streamFor('_content'));

        $this->assertCount(1, $history);

        $request = $history[0]['request'];
        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals('communication_server', $request->getUri());
        $this->assertEquals(['application/x-compress'], $request->getHeader('Content-Type'));
        $this->assertEquals(['Braintacle_local_upload'], $request->getHeader('User-Agent'));
        $this->assertEquals('_content', $request->getBody()->getContents());
    }

    public function testImportStreamError()
    {
        $config = $this->createMock(Config::class);
        $config->method('__get')->with('communicationServerUri')->willReturn('communication_server');

        $httpClient = new HttpClient(['handler' =>  HandlerStack::create(new MockHandler([new Response(500)]))]);

        $importer = new Importer($config, $httpClient);

        $this->expectException(ImportError::class);
        $importer->importStream($this->createStub(StreamInterface::class));
    }

    public function testImportFile()
    {
        $root = vfsStream::setup('root');
        $file = vfsStream::newFile('test.txt')->at($root)->setContent('_content');

        $importer = $this->createPartialMock(Importer::class, ['importStream']);
        $importer->expects($this->once())->method('importStream')->with($this->callback(
            fn(StreamInterface $stream): bool => $stream->getContents() == '_content'
        ));
        $importer->importFile($file->url());
    }
}
