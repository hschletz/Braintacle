<?php

namespace Braintacle\Template;

use Braintacle\Template\Function\AssetUrlFunction;
use Braintacle\Template\Function\CsrfTokenFunction;
use Braintacle\Template\Function\PathForRouteFunction;
use Console\Template\Functions\TranslateFunction;
use Console\Template\TemplateLoader;
use ErrorException;
use Latte\Engine;

/**
 * Template rendering engine.
 */
class TemplateEngine
{
    public function __construct(
        private string $locale,
        private Engine $engine,
        private TemplateLoader $templateLoader,
        private AssetUrlFunction $assetUrlFunction,
        private CsrfTokenFunction $csrfTokenFunction,
        private PathForRouteFunction $pathForRouteFunction,
        private TranslateFunction $translateFunction,
    ) {
        $this->engine->setLocale($locale);
        $this->engine->setLoader($templateLoader);

        $engine->addFunction('assetUrl', $this->assetUrlFunction);
        $engine->addFunction('csrfToken', $this->csrfTokenFunction);
        $engine->addFunction('pathForRoute', $this->pathForRouteFunction);
        $engine->addFunction('translate', $this->translateFunction);
    }

    public function render(string $templatePath, array $params = []): string
    {
        // Catch Latte warnings and throw an exception.
        set_error_handler(function (
            int $errno,
            string $errstr,
            string $errfile = '',
            int $errline = 0,
        ) {
            // If the error suppression operator is used, error_reporting() returns this value.
            $suppressed = E_ERROR | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR | E_PARSE;
            if (error_reporting() != $suppressed) {
                throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
            }
        });

        // Error suppression detection may fail if some bits are unset,
        // resulting in an exception for suppressed errors.
        $errorReporting = error_reporting(E_ALL);
        try {
            return $this->engine->renderToString($templatePath, $params);
        } finally {
            error_reporting($errorReporting);
            restore_error_handler();
        }
    }
}
