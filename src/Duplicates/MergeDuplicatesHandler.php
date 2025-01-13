<?php

namespace Braintacle\Duplicates;

use Braintacle\CsrfProcessor;
use Console\Form\ShowDuplicates as Validator;
use Laminas\Session\Container as Session;
use Model\Client\DuplicatesManager;
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
        private Session $session,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $formData = $this->csrfProcessor->process($request->getParsedBody());
        $this->validator->setData($formData);
        if ($this->validator->isValid()) {
            $data = $this->validator->getData();
            $this->duplicatesManager->merge($data['clients'], $data['mergeOptions']);

            // Signal redirect target about successful merge.
            $this->session[__CLASS__] = true;
            $this->session->setExpirationHops(1, __CLASS__);

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
