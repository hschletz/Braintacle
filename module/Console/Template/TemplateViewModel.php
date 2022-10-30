<?php

namespace Console\Template;

use Laminas\View\Model\ViewModel;

/**
 * View model for template renderer.
 *
 * Return an instance of this view model from a controller action to render a
 * template.
 */
class TemplateViewModel extends ViewModel
{
    public function __construct(string $template, $variables)
    {
        $this->setTemplate($template);
        parent::__construct($variables);
    }
}
