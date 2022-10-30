<?php

namespace Console\Template;

use Console\Template\TemplateRenderer;
use Laminas\EventManager\AbstractListenerAggregate;
use Laminas\EventManager\EventManagerInterface;
use Laminas\View\ViewEvent;

/**
 * Template rendering strategy.
 *
 * Attach this strategy to ViewEvent::EVENT_RENDERER. This will cause the
 * TemplateRenderer to be invoked for TemplateViewModel instances.
 */
class TemplateStrategy extends AbstractListenerAggregate
{
    private TemplateRenderer $templateRenderer;

    public function __construct(TemplateRenderer $templateRenderer)
    {
        $this->templateRenderer = $templateRenderer;
    }

    public function attach(EventManagerInterface $events, $priority = 1)
    {
        $this->listeners[] = $events->attach(ViewEvent::EVENT_RENDERER, [$this, 'selectRenderer'], $priority);
    }

    public function selectRenderer(ViewEvent $e): ?TemplateRenderer
    {
        if ($e->getModel() instanceof TemplateViewModel) {
            return $this->templateRenderer;
        } else {
            return null;
        }
    }
}
