<?php

namespace Braintacle\Test;

use Braintacle\CsrfProcessor;
use Formotron\FormProcessor;

/**
 * Helpers for testing Formotron data objects with CSRF token.
 */
trait CsrfFormProcessorTestTrait
{
    use FormProcessorTestTrait {
        FormProcessorTestTrait::createFormProcessor as private createBaseFormProcessor;
    }

    /**
     * Create form processor with CSRF preprocessor stub.
     *
     * Testing against this form processor will ensure the presence of the
     * CsrfProcessor in the data object.
     */
    protected function createFormProcessor(array $services = []): FormProcessor
    {
        $csrfProcessor = $this->createMock(CsrfProcessor::class);
        $csrfProcessor->expects($this->once())->method('process')->willReturnArgument(0);
        $services[CsrfProcessor::class] = $csrfProcessor;

        return $this->createBaseFormProcessor($services);
    }
}
