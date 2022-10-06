<?php

namespace Console\Template;

use ErrorException;
use Laminas\I18n\Translator\TranslatorInterface;
use Laminas\View\Renderer\RendererInterface;
use Laminas\View\Resolver\ResolverInterface;
use Latte\Engine;
use Latte\Loaders\FileLoader;
use Library\Application;

/**
 * Main interface for template rendering.
 *
 * Templates are loaded from the /templates directory. Functions from the
 * TemplateFunctions class are made available within templates.
 */
class TemplateRenderer implements RendererInterface
{
    private const TEMPLATE_PATH = 'templates';

    private Engine $engine;

    public function __construct(Engine $engine, TranslatorInterface $translator)
    {
        $templateFunctions = new TemplateFunctions($translator);

        $engine->setLoader(new FileLoader(Application::getPath(self::TEMPLATE_PATH)));
        $engine->addFunction('translate', [$templateFunctions, 'translate']);

        $this->engine = $engine;
    }

    public function getEngine(): Engine
    {
        return $this->engine;
    }

    public function setResolver(ResolverInterface $resolver): void
    {
    }

    public function render($nameOrModel, $values = null): string
    {
        // Latte does not catch warnings emitted by template functions. These
        // would show up in template output in unexpected places. Set up an
        // error handler to intercept warnings and convert them into a proper
        // Exception,
        set_error_handler(function (
            int $errno,
            string $errstr,
            string $errfile,
            int $errline
        ) {
            throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
        });

        try {
            return $this->getEngine()->renderToString($nameOrModel, $values);
        } finally {
            restore_error_handler();
        }
    }
}
