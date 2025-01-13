<?php

namespace Console\View\Helper;

use Braintacle\Template\TemplateEngine;
use Laminas\View\Helper\AbstractHelper;
use Model\Client\Client;

/**
 * Render client headline and navigation
 */
class ClientHeader extends AbstractHelper
{
    public function __construct(private TemplateEngine $templateEngine, private string $currentAction)
    {
    }

    public function __invoke(Client $client): string
    {
        return $this->templateEngine->render(
            'Pages/Client/Header.latte',
            [
                'client' => $client,
                'currentAction' => $this->currentAction
            ]
        );
    }
}
