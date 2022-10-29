<?php

namespace Console\View\Helper;

use Console\Template\TemplateRenderer;
use Laminas\View\Helper\AbstractHelper;
use Model\Client\Client;

/**
 * Render client headline and navigation
 */
class ClientHeader extends AbstractHelper
{
    private TemplateRenderer $templateRenderer;
    private string $currentAction;

    public function __construct(TemplateRenderer $templateRenderer, string $currentAction)
    {
        $this->templateRenderer = $templateRenderer;
        $this->currentAction = $currentAction;
    }

    public function __invoke(Client $client): string
    {
        return $this->templateRenderer->render(
            'Client/Header.latte',
            [
                'client' => $client,
                'currentAction' => $this->currentAction
            ]
        );
    }
}
