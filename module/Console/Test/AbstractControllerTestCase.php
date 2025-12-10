<?php

/**
 * Abstract controller test case
 *
 * Copyright (C) 2011-2025 Holger Schletz <holger.schletz@web.de>
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option)
 * any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for
 * more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

namespace Console\Test;

use Laminas\Http\PhpEnvironment\Request;
use Laminas\Http\Response;
use Laminas\Mvc\Application as MvcApplication;
use Laminas\Mvc\MvcEvent;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Stdlib\Parameters;
use Laminas\Uri\Http as Uri;
use Laminas\View\Model\ViewModel;
use Library\Application;
use Library\Test\InjectServicesTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Abstract controller test case
 *
 * This base class performs common setup for all controller tests and implements
 * functionality formerly provided by laminas-test.
 */
abstract class AbstractControllerTestCase extends TestCase
{
    use InjectServicesTrait;

    protected ?MvcApplication $application;
    private array $applicationConfig;

    /**
     * Set up application config
     */
    public function setUp(): void
    {
        $this->reset();
        $this->applicationConfig = Application::getApplicationConfig('Console');

        // Put application in authenticated state
        $auth = $this->createMock('Model\Operator\AuthenticationService');
        $auth->method('hasIdentity')->willReturn(true);

        $serviceManager = $this->getApplicationServiceLocator();
        $serviceManager->setService('Model\Operator\AuthenticationService', $auth);
        Application::addAbstractFactories($serviceManager);
        self::injectServices($serviceManager);

        // Prevent the MVC application from applying a layout. Unlike in the
        // real application, the listener must not be attached to the
        // MvcEvent::EVENT_RENDER_ERROR. That would break
        // AbstractHttpControllerTestCase's error handling.
        $eventManager = $this->getApplication()->getEventManager();
        $eventManager->attach(MvcEvent::EVENT_RENDER, function (MvcEvent $event) {
            $result = $event->getResult();
            if ($result instanceof ViewModel) {
                $result->setTerminal(true);
                $event->setViewModel($result);
            }
        }, -95);

        // Prevent the MVC application from applying an error template. Unlike
        // in the real application, assume an exception and throw it.
        $eventManager->attach(MvcEvent::EVENT_DISPATCH_ERROR, function (MvcEvent $event) {
            throw $event->getParam('exception');
        }, -10);
        $eventManager->attach(MvcEvent::EVENT_RENDER_ERROR, function (MvcEvent $event) {
            throw $event->getParam('exception');
        }, -10);
    }

    public function tearDown(): void
    {
        $this->reset();
    }

    private function reset(): void
    {
        $this->application = null;

        if (array_key_exists('_SESSION', $GLOBALS)) {
            $_SESSION = [];
        }
        $_COOKIE = [];
        $_GET  = [];
        $_POST = [];
    }

    /**
     * Get instance of a controller plugin
     *
     * @param string $name Plugin name
     * @return \Laminas\Mvc\Controller\Plugin\PluginInterface Plugin instance
     */
    protected function getControllerPlugin($name)
    {
        return $this->getApplicationServiceLocator()->get('ControllerPluginManager')->get($name);
    }

    /**
     * Replace MvcTranslator service with a dummy translator to allow injecting test messages without warning
     */
    protected function disableTranslator()
    {
        $serviceManager = $this->getApplicationServiceLocator();
        $serviceManager->setAllowOverride(true);
        $serviceManager->setService(
            'MvcTranslator',
            new \Laminas\Mvc\I18n\Translator(new \Laminas\I18n\Translator\Translator())
        );
    }

    /**
     * Bypass all MvcEvent::EVENT_RENDER listeners.
     *
     * This is useful to test the action result directly via assertMvcResult().
     */
    protected function interceptRenderEvent(): void
    {
        $this->application->getEventManager()->attach(
            MvcEvent::EVENT_RENDER,
            function ($event) {
                $event->stopPropagation(true);
            },
            100
        );
    }

    /**
     * Test result of MVC action.
     */
    public function assertMvcResult($result)
    {
        $this->assertSame(
            $result,
            $this->getApplication()->getMvcEvent()->getResult(),
            'Failed asserting the MVC result.'
        );
    }

    private function getApplication()
    {
        if (!$this->application) {
            $this->application = MvcApplication::init($this->applicationConfig);

            $eventManager = $this->application->getEventManager();
            $this->application->getServiceManager()->get('SendResponseListener')->detach($eventManager);
        }

        return $this->application;
    }

    protected function getApplicationServiceLocator(): ServiceManager
    {
        return $this->getApplication()->getServiceManager();
    }

    protected function dispatch(string $url, ?string $method = Request::METHOD_GET, ?array $params = [])
    {
        assert($method == Request::METHOD_GET || $method == Request::METHOD_POST);

        $request = $this->getRequest();
        $query = $request->getQuery()->toArray();
        $post = $request->getPost()->toArray();
        $uri = new Uri($url);

        $queryString = $uri->getQuery();
        if ($queryString) {
            parse_str($queryString, $query);
        }

        if ($params) {
            if ($method == Request::METHOD_GET) {
                $query = array_merge($query, $params);
            } else { // POST
                $post = $params;
            }
        }

        $request->setMethod($method);
        $request->setQuery(new Parameters($query));
        $request->setPost(new Parameters($post));
        $request->setUri($uri);
        $request->setRequestUri($uri->getPath());

        $this->getApplication()->run();
    }

    protected function getRequest(): Request
    {
        return $this->getApplication()->getRequest();
    }

    protected function getResponse(): Response
    {
        return $this->getApplication()->getMvcEvent()->getResponse();
    }

    protected function assertResponseStatusCode(int $code): void
    {
        $this->assertEquals($code, $this->getResponse()->getStatusCode());
    }

    protected function assertRedirectTo(string $url): void
    {
        $responseHeader = $this->getResponse()->getHeaders()->get('Location');
        $this->assertNotEmpty($responseHeader);
        $this->assertEquals($url, $responseHeader->getFieldValue());
    }

    protected function assertQuery(string $path): void
    {
        $this->assertGreaterThan(0, count($this->query($path, false)));
    }

    protected function assertNotQuery(string $path): void
    {
        $this->assertEquals(0, count($this->query($path, false)));
    }

    protected function assertQueryContentContains(string $path, string $match): void
    {
        $this->queryContentContainsAssertion($path, $match, false);
    }

    protected function assertNotQueryContentContains(string $path, string $match): void
    {
        $this->notQueryContentContainsAssertion($path, $match, false);
    }

    protected function assertXpathQuery(string $path): void
    {
        $this->assertGreaterThan(0, $this->query($path, true)->count());
    }

    protected function assertNotXpathQuery(string $path): void
    {
        $this->assertEquals(0, $this->query($path, true)->count());
    }

    protected function assertXpathQueryContentContains(string $path, string $match): void
    {
        $this->queryContentContainsAssertion($path, $match, true);
    }

    protected function assertNotXpathQueryContentContains(string $path, string $match): void
    {
        $this->notQueryContentContainsAssertion($path, $match, true);
    }

    protected function assertXpathQueryCount(string $path, int $count): void
    {
        $this->assertEquals($count, $this->query($path, true)->count());
    }

    private function queryContentContainsAssertion(string $path, string $match, bool $useXpath): void
    {
        $result = $this->query($path, $useXpath);
        $this->assertNotEquals(0, $result->count());

        $nodeValues = [];
        foreach ($result as $node) {
            if ($node->nodeValue === $match) {
                $this->assertEquals($match, $node->nodeValue);
                return;
            }
            $nodeValues[] = $node->nodeValue;
        }

        $this->fail(sprintf(
            'Failed asserting node denoted by %s CONTAINS content "%s", Contents: [%s]',
            $path,
            $match,
            implode(',', $nodeValues)
        ));
    }

    private function notQueryContentContainsAssertion(string $path, string $match, bool $useXpath): void
    {
        $result = $this->query($path, $useXpath);
        $this->assertNotEquals(0, $result->count());
        foreach ($result as $node) {
            $this->assertNotEquals($match, $node->nodeValue);
        }
    }

    private function query(string $path, bool $useXpath)
    {
        $response = $this->getResponse();
        $document = new Crawler($response->getContent());

        return $useXpath ? $document->filterXPath($path) : $document->filter($path);
    }
}
