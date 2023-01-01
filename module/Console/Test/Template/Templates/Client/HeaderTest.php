<?php

namespace Console\Test\Template\Templates\Client;

use Console\Template\TemplateRenderer;
use Latte\Engine;
use Library\Test\DomMatcherTrait;
use Model\Client\Client;
use Model\Client\WindowsInstallation;
use PHPUnit\Framework\TestCase;

class HeaderTest extends TestCase
{
    use DomMatcherTrait;

    private function createEngine(): Engine
    {
        $engine = new Engine();
        $engine->setLoader(TemplateRenderer::createLoader());
        $engine->addFunction('translate', fn($message) => $message);
        $engine->addFunction(
            'consoleUrl',
            fn ($controller, $action, $queryParams) => "/$controller/$action/?" . http_build_query($queryParams)
        );

        return $engine;
    }

    public function testMenuForWindowsClients()
    {
        $client = new Client();
        $client->id = 1;
        $client->name = 'name';
        $client->windows = new WindowsInstallation();

        $engine = $this->createEngine();
        $content = $engine->renderToString('Client/Header.latte', ['client' => $client, 'currentAction' => '']);
        $document = $this->createDocument($content);

        $query = '//ul[contains(concat(" ", normalize-space(@class), " "), " navigation_details ")]/li';
        $this->assertXpathMatches($document, $query . '/a[@href="/client/windows/?id=1"]');
        $this->assertXpathMatches($document, $query . '/a[@href="/client/msoffice/?id=1"]');
        $this->assertXpathMatches($document, $query . '/a[@href="/client/registry/?id=1"]');
    }

    public function testMenuForNonWindowsClients()
    {
        $client = new Client();
        $client->id = 1;
        $client->name = 'name';
        $client->windows = null;

        $engine = $this->createEngine();
        $content = $engine->renderToString('Client/Header.latte', ['client' => $client, 'currentAction' => '']);
        $document = $this->createDocument($content);

        $query = '//ul[contains(concat(" ", normalize-space(@class), " "), " navigation_details ")]/li';
        $this->assertXpathMatches($document, $query); // Avoid false positives when rendering fails
        $this->assertNotXpathMatches($document, $query . '/a[@href="/client/windows/?id=1"]');
        $this->assertNotXpathMatches($document, $query . '/a[@href="/client/msoffice/?id=1"]');
        $this->assertNotXpathMatches($document, $query . '/a[@href="/client/registry/?id=1"]');
    }
}
