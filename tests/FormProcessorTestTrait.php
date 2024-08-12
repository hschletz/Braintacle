<?php

namespace Braintacle\Test;

use Braintacle\CsrfProcessor;
use DI\Container;
use Formotron\FormProcessor;

/**
 * Helpers for testing Formotron data objects.
 */
trait FormProcessorTestTrait
{
    /**
     * Create form processor, ensure CSRF token validation.
     */
    protected function createFormProcessor(): FormProcessor
    {
        $csrfProcessor = $this->createMock(CsrfProcessor::class);
        $csrfProcessor->expects($this->once())->method('process')->willReturnArgument(0);
        $container = new Container([CsrfProcessor::class => $csrfProcessor]);
        $formProcessor = $container->get(FormProcessor::class);

        return $formProcessor;
    }
}
