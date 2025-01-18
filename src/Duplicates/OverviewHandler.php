<?php

namespace Braintacle\Duplicates;

use Braintacle\Duplicates\Criterion;
use Braintacle\FlashMessages;
use Braintacle\Template\TemplateEngine;
use Model\Client\DuplicatesManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Display overview of duplicates.
 */
class OverviewHandler implements RequestHandlerInterface
{
    public function __construct(
        private ResponseInterface $response,
        private DuplicatesManager $duplicatesManager,
        private TemplateEngine $templateEngine,
        private FlashMessages $flashMessages,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $duplicates = [];
        foreach (Criterion::cases() as $criterion) {
            $count = $this->duplicatesManager->count($criterion);
            if ($count) {
                $duplicates[$criterion->value] = $count;
            }
        }
        $this->response->getBody()->write($this->templateEngine->render('Pages/Duplicates/Overview.latte', [
            'duplicates' => $duplicates,
            'message' => $this->flashMessages->get(FlashMessages::Success)[0] ?? null,
        ]));

        return $this->response;
    }
}
