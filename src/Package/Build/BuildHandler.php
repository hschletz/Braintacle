<?php

namespace Braintacle\Package\Build;

use Braintacle\Http\RouteHelper;
use Braintacle\Legacy\Plugin\FlashMessenger;
use Braintacle\Package\Package;
use Braintacle\Package\PackageUpdate;
use Braintacle\Template\TemplateEngine;
use Formotron\DataProcessor;
use Laminas\Translator\TranslatorInterface;
use Model\Package\RuntimeException as PackageRuntimeException;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Exception\HttpBadRequestException;

/**
 * Build/update package.
 */
final class BuildHandler implements RequestHandlerInterface
{
    public function __construct(
        private ResponseInterface $response,
        private DataProcessor $dataProcessor,
        private TranslatorInterface $translator,
        private Builder $builder,
        private FlashMessenger $flashMessenger,
        private RouteHelper $routeHelper,
        private TemplateEngine $templateEngine,
    ) {}

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $file = $request->getUploadedFiles()['file'] ?? null;
        if (! $file instanceof UploadedFileInterface) {
            throw new HttpBadRequestException($request, 'Bad file');
        }

        /** @var ?string */
        $updateFrom = $request->getQueryParams()['updateFrom'] ?? null;
        $formData = $request->getParsedBody();
        try {
            $package = $this->dataProcessor->process(
                $formData,
                $updateFrom ? PackageUpdate::class : Package::class,
            );
        } catch (ValidationErrors $validationErrors) {
            $this->response->getBody()->write($this->templateEngine->render(
                'Pages/Package/Build.latte',
                $formData + [
                    'isUpdate' => $updateFrom !== null,
                    'errors' => $validationErrors,
                ],
            ));

            return $this->response;
        }

        if ($updateFrom) {
            $this->update($package, $file, $updateFrom);
        } else {
            $this->build($package, $file);
        }

        return $this->response->withStatus(302)->withHeader(
            'Location',
            $this->routeHelper->getPathForRoute('packagesList'),
        );
    }

    private function build(Package $package, UploadedFileInterface $file): void
    {
        try {
            $this->builder->build($package, $file);
            $this->flashMessenger->addSuccessMessage(
                sprintf(
                    $this->translator->translate("Package '%s' was successfully created."),
                    $package->name,
                )
            );
        } catch (PackageRuntimeException $exception) {
            $this->flashMessenger->addErrorMessage($exception->getMessage());
        }
    }

    private function update(PackageUpdate $package, UploadedFileInterface $file, string $updateFrom)
    {
        try {
            $this->builder->update($package, $file, $updateFrom);
            $this->flashMessenger->addSuccessMessage(
                sprintf(
                    $this->translator->translate("Package '%1\$s' was successfully changed to '%2\$s'."),
                    $updateFrom,
                    $package->name,
                )
            );
        } catch (PackageRuntimeException $exception) {
            $this->flashMessenger->addErrorMessage(
                sprintf(
                    $this->translator->translate("Error changing Package '%1\$s' to '%2\$s': %3\$s"),
                    $updateFrom,
                    $package->name,
                    $exception->getMessage()
                )
            );
        }
    }
}
