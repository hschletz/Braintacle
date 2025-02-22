<?php

namespace Braintacle\Duplicates;

use Braintacle\CsrfProcessor;
use Braintacle\FlashMessages;
use Console\Form\ShowDuplicates as Validator;
use Laminas\Translator\TranslatorInterface;
use Model\Client\DuplicatesManager;
use Override;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RecursiveArrayIterator;
use RecursiveIteratorIterator;

/**
 * Merge duplicate clients.
 */
class MergeDuplicatesHandler implements RequestHandlerInterface
{
    public function __construct(
        private ResponseInterface $response,
        private CsrfProcessor $csrfProcessor,
        private Validator $validator,
        private DuplicatesManager $duplicatesManager,
        private FlashMessages $flashMessages,
        private TranslatorInterface $translator,
    ) {}

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $formData = $this->csrfProcessor->process($request->getParsedBody());
        $this->validator->setData($formData);
        if ($this->validator->isValid()) {
            $data = $this->validator->getData();
            $this->duplicatesManager->merge($data['clients'], $data['mergeOptions']);
            $this->flashMessages->add(
                FlashMessages::Success,
                $this->translator->translate('The selected clients have been merged.')
            );

            return $this->response;
        } else {
            // Flatten messages to a single-level list.
            $messages = $this->validator->getMessages();
            $messages = new RecursiveIteratorIterator(new RecursiveArrayIterator($messages));
            $messages = array_values(iterator_to_array($messages));

            $this->response->getBody()->write(json_encode($messages));

            return $this->response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
    }
}
