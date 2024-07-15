<?php

namespace Braintacle\Http;

use Braintacle\AppConfig;
use Braintacle\Template\TemplateEngine;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpException;
use Symfony\Component\VarDumper\Cloner\ClonerInterface;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;
use Throwable;

/**
 * Catch and handle all errors.
 */
class ErrorHandlingMiddleware implements MiddlewareInterface
{
    private ClonerInterface $cloner;
    private CliDumper $dumper;

    public function __construct(
        private ResponseInterface $response,
        private AppConfig $appConfig,
        private LoggerInterface $logger,
        private TemplateEngine $templateEngine,
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

            $debug = $this->appConfig->debug['display backtrace'] ?? false;
            $params = [
                'message' => $throwable->getMessage(),
                'debug' => $debug,
            ];
            if ($debug) {
                $params += $this->getDebugInfo($request, $throwable);
            }

            $response->getBody()->write($this->templateEngine->render('Pages/Error.latte', $params));
        }

        return $response;
    }

    private function getDebugInfo(ServerRequestInterface $request, Throwable $throwable): array
    {
        // HTMLDumper's output is rather verbose and makes debugging the error
        // page output harder. CliDumper without colors is effectively a simple
        // plaintext dumper, which is sufficient.
        $this->cloner = new VarCloner();
        $this->dumper = new CliDumper();
        $this->dumper->setColors(false);

        return [
            'debug' => true,
            'throwable' => $this->dump($throwable),
            'method' => $request->getMethod(),
            'query' => $this->dump($request->getQueryParams()),
            'post' => $this->dump($request->getParsedBody() ?? []),
            'files' => $this->dump(
                array_map(
                    fn (UploadedFileInterface $uploadedFile) => [
                        'clientFilename' => $uploadedFile->getClientFilename(),
                        'clientMediaType' => $uploadedFile->getClientMediaType(),
                        'size' => $uploadedFile->getSize(),
                        'error' => $uploadedFile->getError(),
                    ],
                    $request->getUploadedFiles()
                )
            ),
            'env' => $this->dump($request->getServerParams()),
        ];
    }

    private function dump(mixed $var): string
    {
        return $this->dumper->dump($this->cloner->cloneVar($var), true);
    }
}
