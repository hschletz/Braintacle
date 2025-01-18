<?php

namespace Braintacle\Duplicates;

use Braintacle\FlashMessages;
use Formotron\DataProcessor;
use Laminas\Translator\TranslatorInterface;
use Model\Client\DuplicatesManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Allow value of given criterion as duplicate.
 */
class AllowDuplicatesHandler implements RequestHandlerInterface
{
    public function __construct(
        private ResponseInterface $response,
        private DataProcessor $dataProcessor,
        private DuplicatesManager $duplicatesManager,
        private FlashMessages $flashMessages,
        private TranslatorInterface $translator,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $formData = $this->dataProcessor->process($request->getParsedBody(), AllowDuplicatesRequestParameters::class);
        $this->duplicatesManager->allow($formData->criterion, $formData->value);
        $this->flashMessages->add(
            FlashMessages::Success,
            sprintf($this->translator->translate("'%s' is no longer considered duplicate."), $formData->value)
        );

        return $this->response;
    }
}
