<?php

namespace Console\Template;

use ErrorException;
use Laminas\View\Model\ViewModel;
use Laminas\View\Renderer\RendererInterface;
use Laminas\View\Resolver\ResolverInterface;
use Latte\Engine;
use Library\Application;

/**
 * Main interface for template rendering.
 *
 * Templates are loaded from the /templates directory.
 */
class TemplateRenderer implements RendererInterface
{
    private const TEMPLATE_PATH = 'templates';

    private Engine $engine;

    public function __construct(Engine $engine)
    {
        $engine->setLoader(new TemplateLoader(Application::getPath(self::TEMPLATE_PATH)));

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
        if ($nameOrModel instanceof ViewModel) {
            $template = $nameOrModel->getTemplate();
            $values = $nameOrModel->getVariables();
        } else {
            $template = $nameOrModel;
        }

        // Latte does not catch warnings emitted by template functions. These
        // would show up in template output in unexpected places. Set up an
        // error handler to intercept warnings and convert them into a proper
        // Exception,
        set_error_handler(function (
            int $errno,
            string $errstr,
            string $errfile = '',
            int $errline = 0,
            array $errContext = []
        ): ?bool {
            // @codeCoverageIgnoreStart
            // only one branch will be executed depending on PHP version.
            if (PHP_MAJOR_VERSION < 8) {
                $suppressed = 0;
            } else {
                $suppressed = E_ERROR | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR | E_PARSE;
            }
            // @codeCoverageIgnoreEnd
            if (error_reporting() != $suppressed) {
                throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
            }
            return true;
        });

        try {
            return $this->getEngine()->renderToString($template, $values);
        } finally {
            restore_error_handler();
        }
    }
}
