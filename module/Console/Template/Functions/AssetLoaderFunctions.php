<?php

namespace Console\Template\Functions;

use Console\View\Helper\ConsoleScript;

/**
 * Functions for loading web assets (scripts, stylesheets...)
 */
class AssetLoaderFunctions
{
    private ConsoleScript $consoleScript;

    public function __construct(ConsoleScript $consoleScript)
    {
        $this->consoleScript = $consoleScript;
    }

    /**
     * Add a script to the page template.
     */
    public function addScript(string $name): void
    {
        ($this->consoleScript)($name);
    }
}
