<?php

namespace Braintacle\Template;

use Braintacle\Template\Function\AssetUrlFunction;
use Braintacle\Template\Function\CsrfTokenFunction;
use Braintacle\Template\Function\PathForRouteFunction;
use Console\Template\Functions\TranslateFunction;
use Console\Template\TemplateLoader;
use Latte\Engine;

/**
 * Template rendering engine.
 */
class TemplateEngine
{
    public function __construct(
        private Engine $engine,
        private TemplateLoader $templateLoader,
        private AssetUrlFunction $assetUrlFunction,
        private CsrfTokenFunction $csrfTokenFunction,
        private PathForRouteFunction $pathForRouteFunction,
        private TranslateFunction $translateFunction,
    ) {
        $this->engine->setLoader($templateLoader);

        $engine->addFunction('assetUrl', $this->assetUrlFunction);
        $engine->addFunction('csrfToken', $this->csrfTokenFunction);
        $engine->addFunction('pathForRoute', $this->pathForRouteFunction);
        $engine->addFunction('translate', $this->translateFunction);
    }

    public function render(string $templatePath, array $params = []): string
    {
        return $this->engine->renderToString($templatePath, $params);
    }
}
