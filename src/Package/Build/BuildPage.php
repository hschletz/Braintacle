<?php

namespace Braintacle\Package\Build;

use Braintacle\Http\RouteHelper;
use Braintacle\Legacy\Plugin\FlashMessenger;
use Braintacle\Template\TemplateEngine;
use Model\Config;
use Model\Package\PackageManager;
use Model\Package\RuntimeException as PackageRuntimeException;
use Override;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Package build/update form.
 */
final class BuildPage implements RequestHandlerInterface
{
    public function __construct(
        private ResponseInterface $response,
        private RouteHelper $routeHelper,
        private PackageManager $packageManager,
        private Config $config,
        private FlashMessenger $flashMessenger,
        private TemplateEngine $templateEngine,
    ) {}

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $updateFrom = $request->getQueryParams()['updateFrom'] ?? null;
        if ($updateFrom) {
            try {
                $package = $this->packageManager->getPackage($updateFrom);
            } catch (PackageRuntimeException $exception) {
                $this->flashMessenger->addErrorMessage($exception->getMessage());

                return $this->response->withStatus(302)->withHeader(
                    'Location',
                    $this->routeHelper->getPathForRoute('packagesList'),
                );
            }
            $context = [
                'isUpdate' => true,
                'name' => $package->name,
                'comment' => $package->comment,
                'platform' => $package->platform,
                'action' => $package->deployAction,
                'actionParam' => $package->actionParam,
                'priority' => $package->priority,
                'maxFragmentSize' => $this->config->defaultMaxFragmentSize,
                'warn' => $package->warn,
                'warnMessage' => $package->warnMessage,
                'warnCountdown' => $package->warnCountdown,
                'warnAllowAbort' => $package->warnAllowAbort,
                'warnAllowDelay' => $package->warnAllowDelay,
                'postInstMessage' => $package->postInstMessage,
                'deployPending' => $this->config->defaultDeployPending,
                'deployRunning' => $this->config->defaultDeployRunning,
                'deploySuccess' => $this->config->defaultDeploySuccess,
                'deployError' => $this->config->defaultDeployError,
                'deployGroups' => $this->config->defaultDeployGroups,
            ];
        } else {
            $context = [
                'isUpdate' => false,
                'platform' => $this->config->defaultPlatform,
                'action' => $this->config->defaultAction,
                'actionParam' => $this->config->defaultActionParam,
                'priority' => $this->config->defaultPackagePriority,
                'maxFragmentSize' => $this->config->defaultMaxFragmentSize,
                'warn' => $this->config->defaultWarn,
                'warnMessage' => $this->config->defaultWarnMessage,
                'warnCountdown' => $this->config->defaultWarnCountdown,
                'warnAllowAbort' => $this->config->defaultWarnAllowAbort,
                'warnAllowDelay' => $this->config->defaultWarnAllowDelay,
                'postInstMessage' => $this->config->defaultPostInstMessage,
            ];
        }
        $this->response->getBody()->write($this->templateEngine->render('Pages/Package/Build.latte', $context));

        return $this->response;
    }
}
