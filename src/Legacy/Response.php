<?php

namespace Braintacle\Legacy;

/**
 * Minimal response reimplementation.
 *
 * Header handling deviates from the original Laminas-Http implementation. They
 * are handled as a simple array, and multiple values per header are not
 * supported.
 */
final class Response
{
    private int $statusCode = 200;
    private array $headers = [];
    private string $content = '';

    public function setStatusCode(int $statusCode): void
    {
        $this->statusCode = $statusCode;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function setHeader(string $name, string $value): void
    {
        $this->headers[$name] = $value;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function setContent(?string $content): void
    {
        $this->content = $content ?? '';
    }

    public function getContent(): string
    {
        return $this->content;
    }
}
