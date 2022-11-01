<?php

namespace Console\Template;

use Console\Template\TemplateRenderer;
use Laminas\EventManager\AbstractListenerAggregate;
use Laminas\EventManager\EventManagerInterface;
use Laminas\View\ViewEvent;

/**
 * Template rendering strategy.
 *
 * Attach this strategy to the view event manager. This will cause the
 * TemplateRenderer to be invoked for TemplateViewModel instances.
 *
 * The public properties need to be populated with values from the current
 * request. They will be injected as variables into the view model.
 */
class TemplateStrategy extends AbstractListenerAggregate
{
    public string $currentAction;

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
        $model = $e->getModel();
        if ($model instanceof TemplateViewModel) {
            $model->setVariable('currentAction', $this->currentAction);
            return $this->templateRenderer;
        } else {
            return null;
        }
    }
}
