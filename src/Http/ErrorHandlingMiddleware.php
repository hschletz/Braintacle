<?php

namespace Braintacle\Http;

use Braintacle\AppConfig;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpException;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;
use Throwable;

/**
 * Catch and handle all errors.
 */
class ErrorHandlingMiddleware implements MiddlewareInterface
{
    public function __construct(
        private ResponseInterface $response,
        private AppConfig $appConfig,
        private LoggerInterface $logger,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            $response = $handler->handle($request);
        } catch (Throwable $throwable) {
            $this->logger->error('', ['exception' => $throwable]);

            $statusCode = $throwable instanceof HttpException ? $throwable->getCode() : 500;
            $response = $this->response->withStatus($statusCode);

            $body = $response->getBody();
            $body->write(htmlspecialchars($throwable->getMessage()));
            if ($this->appConfig->debug['display backtrace'] ?? false) {
                $cloner = new VarCloner();
                $dumper = new HtmlDumper();
                $body->write($dumper->dump($cloner->cloneVar($throwable), true));
            }
        }

        return $response;
    }
}
