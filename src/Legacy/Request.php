<?php

namespace Braintacle\Legacy;

use Laminas\Stdlib\Message;
use Laminas\Stdlib\Parameters;
use Laminas\Stdlib\ParametersInterface;
use Laminas\Stdlib\RequestInterface;
use Laminas\Uri\Http as Uri;

/**
 * Minimal request reimplementation.
 */
final class Request extends Message implements RequestInterface
{
    public const METHOD_GET      = 'GET';
    public const METHOD_POST     = 'POST';

    private string $method;
    private Uri $uri;
    private ParametersInterface $queryParams;
    private ParametersInterface $postParams;

    public function setMethod(string $method): void
    {
        $this->method = $method;
    }

    public function isGet(): bool
    {
        return $this->method == self::METHOD_GET;
    }

    public function isPost(): bool
    {
        return $this->method == self::METHOD_POST;
    }

    public function setUri(Uri|string $uri): void
    {
        if (is_string($uri)) {
            $uri = new Uri($uri);
        }

        $this->uri = $uri;
    }

    public function getUri(): Uri // Required by Laminas-Router
    {
        return $this->uri;
    }

    public function setQuery(ParametersInterface $parameters): void
    {
        $this->queryParams = $parameters;
    }

    public function getQuery(?string $name = null, ?string $default = null): ParametersInterface|string|null
    {
        if (!isset($this->queryParams)) {
            $this->queryParams = new Parameters();
        }

        if ($name === null) {
            return $this->queryParams;
        } else {
            return $this->queryParams->get($name, $default);
        }
    }

    public function setPost(ParametersInterface $parameters): void
    {
        $this->postParams = $parameters;
    }

    public function getPost(?string $name = null): ParametersInterface|string|null
    {
        if (!isset($this->postParams)) {
            $this->postParams = new Parameters();
        }

        if ($name === null) {
            return $this->postParams;
        } else {
            return $this->postParams->get($name);
        }
    }
}
