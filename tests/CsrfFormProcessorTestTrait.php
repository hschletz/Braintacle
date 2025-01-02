<?php

namespace Braintacle\Test;

use Braintacle\CsrfProcessor;
use Formotron\DataProcessor;

/**
 * Helpers for testing Formotron data objects with CSRF token.
 */
trait CsrfFormProcessorTestTrait
{
    use DataProcessorTestTrait {
        DataProcessorTestTrait::createDataProcessor as private createBaseDataProcessor;
    }

    /**
     * Create form processor with CSRF preprocessor stub.
     *
     * Testing against this form processor will ensure the presence of the
     * CsrfProcessor in the data object.
     */
    protected function createDataProcessor(array $services = []): DataProcessor
    {
        $csrfProcessor = $this->createMock(CsrfProcessor::class);
        $csrfProcessor->expects($this->once())->method('process')->willReturnArgument(0);
        $services[CsrfProcessor::class] = $csrfProcessor;

        return $this->createBaseDataProcessor($services);
    }
}
