<?php

namespace Console\Template\Extensions;

use Console\View\Helper\ConsoleScript;
use Latte\Compiler\Node;
use Latte\Compiler\Nodes\NopNode;
use Latte\Compiler\PrintContext;
use Latte\Compiler\Tag;
use Latte\Extension;

/**
 * Tags for loading web assets (scripts, stylesheets...)
 *
 * Usage:
 *
 * {addScript scriptname.js}
 *
 * The tag may be used anywhere within a template. It does not generate output
 * by itself, but adds a sctipt to the ConsoleScript helper where it will be
 * evaluated by the page layout.
 *
 * @deprecated Add assets via AssetUrlFunction.
 */
class AssetLoaderExtension extends Extension
{
    public function __construct(private ConsoleScript $consoleScript)
    {
        $this->consoleScript = $consoleScript;
    }

    public function getTags(): array
    {
        return [
            'addScript' => $this->addScript(...),
        ];
    }

    /**
     * Add a script to the page output.
     */
    public function addScript(Tag $tag): Node
    {
        $tag->expectArguments();
        $expression = $tag->parser->parseExpression()->print(new PrintContext());
        $name = null;
        eval("\$name = $expression;");
        ($this->consoleScript)($name);

        return new NopNode();
    }
}
