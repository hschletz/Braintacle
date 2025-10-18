<?php

namespace Braintacle\Group;

use Braintacle\FlashMessages;
use Formotron\DataProcessor;
use Laminas\Translator\TranslatorInterface;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

class DeleteHandler implements RequestHandlerInterface
{
    public function __construct(
        private ResponseInterface $response,
        private DataProcessor $dataProcessor,
        private Groups $groups,
        private FlashMessages $flashMessages,
        private TranslatorInterface $translator,
    ) {}

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $group = $this->dataProcessor->process($request->getQueryParams(), GroupRequestParameters::class)->group;
        try {
            $this->groups->deleteGroup($group);
            $this->flashMessages->add(
                FlashMessages::Success,
                sprintf($this->translator->translate("Group '%s' was successfully deleted."), $group->name)
            );

            return $this->response;
        } catch (RuntimeException) {
            $this->response->getBody()->write(
                sprintf($this->translator->translate("Group '%s' could not be deleted. Try again later."), $group->name)
            );

            return $this->response->withStatus(500)->withHeader('Content-Type', 'text/plain');
        }
    }
}
