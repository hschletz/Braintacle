<?php

namespace Braintacle\Legacy;

use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\Router\RouteMatch;
use Laminas\Router\RouteStackInterface;
use Laminas\View\Model\ViewModel;

final class MvcEvent
{
    private array $params = [];
    private Request $request;
    private Response $response;
    private array|Response|ViewModel $result;
    private RouteStackInterface $router;
    private RouteMatch $routeMatch;

    public function getParam(string $name): mixed
    {
        return $this->params[$name] ?? null;
    }

    public function setParam(string $name, mixed $value): void
    {
        $this->params[$name] = $value;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function setRequest(Request $request): void
    {
        $this->request = $request;
    }

    public function getResponse(): Response
    {
        return $this->response;
    }

    public function setResponse(Response $response): void
    {
        $this->response = $response;
    }

    public function getResult(): array|Response|ViewModel
    {
        return $this->result;
    }

    public function setResult(array|Response|ViewModel $result): void
    {
        $this->result = $result;
    }

    public function getRouter(): RouteStackInterface
    {
        return $this->router;
    }

    public function setRouter(RouteStackInterface $router): void
    {
        $this->router = $router;
    }

    public function getRouteMatch(): RouteMatch
    {
        return $this->routeMatch;
    }

    public function setRouteMatch(RouteMatch $routeMatch): void
    {
        $this->routeMatch = $routeMatch;
    }
}
