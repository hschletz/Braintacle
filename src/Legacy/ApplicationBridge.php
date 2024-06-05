<?php

namespace Braintacle\Legacy;

use Braintacle\AppConfig;
use Laminas\Db\Adapter\Adapter;
use Laminas\Http\Header\HeaderInterface;
use Laminas\Http\Response as MvcResponse;
use Laminas\Mvc\Application;
use Laminas\Mvc\MvcEvent;
use Nada\Database\AbstractDatabase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Invoke the MVC application.
 */
class ApplicationBridge implements RequestHandlerInterface
{
    private MvcResponse $mvcResponse;

    public function __construct(
        private ResponseInterface $response,
        private ContainerInterface $container,
        private Application $application,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Inject Services which are not provided by module's ServiceManager
        // configuration.
        $serviceManager = $this->application->getServiceManager();
        $serviceManager->setService(AbstractDatabase::class, $this->container->get(AbstractDatabase::class));
        $serviceManager->setService(Adapter::class, $this->container->get(Adapter::class));
        $serviceManager->setService(AppConfig::class, $this->container->get(AppConfig::class));
        $serviceManager->setAlias('Database\Nada', AbstractDatabase::class);
        $serviceManager->setAlias('Db', Adapter::class);

        // Prevent the MVC application from generating output. Capture the MVC
        // response instead.
        $this->application->getEventManager()->attach(MvcEvent::EVENT_FINISH, function (MvcEvent $event) {
            $event->stopPropagation();
            $this->mvcResponse = $event->getResponse();
        });

        // run() triggers a warning. This seems to be caused by inconsistent
        // Container interface usage throughout the Laminas code and cannot be
        // fixed here. Suppress the warning via a custom error handler.
        // suppression via @ would suppress all warnings from our own code, too.
        set_error_handler(
            fn (int $errno, string $errstr) => str_starts_with(
                $errstr,
                'Laminas\ServiceManager\AbstractPluginManager::__construct now expects a '
            ),
            E_USER_DEPRECATED
        );
        try {
            $this->application->run();
        } finally {
            restore_error_handler();
        }

        // Generate PSR-7 response from MVC response.
        $response = $this->response->withStatus($this->mvcResponse->getStatusCode());
        /** @var HeaderInterface $header */
        foreach ($this->mvcResponse->getHeaders() as $header) {
            $response = $response->withAddedHeader($header->getFieldName(), $header->getFieldValue());
        }
        $response->getBody()->write($this->mvcResponse->getContent());

        return $response;
    }
}
