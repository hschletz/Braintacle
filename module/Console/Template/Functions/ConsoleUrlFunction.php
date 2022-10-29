<?php

namespace Console\Template\Functions;

use Console\View\Helper\ConsoleUrl;

/**
 * Generate URL to given controller and action
 */
class ConsoleUrlFunction
{
    private ConsoleUrl $consoleUrl;

    public function __construct(ConsoleUrl $consoleUrl)
    {
        $this->consoleUrl = $consoleUrl;
    }

    /**
     * Generate URL to given controller and action
     *
     * @param string $controller Optional controller name (default: current controller)
     * @param string $action Optional action name (default: current action)
     * @param array<string, string> $params Optional associative array of query parameters
     * @param bool $inheritParams Include request query parameters. Parameters in $params take precedence.
     */
    public function __invoke(
        string $controller = null,
        string $action = null,
        array $params = [],
        bool $inheritParams = false
    ): string {
        return ($this->consoleUrl)($controller, $action, $params, $inheritParams);
    }
}
