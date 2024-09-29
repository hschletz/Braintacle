<?php

namespace Braintacle\Test;

use DI\Container;
use Formotron\AssertionFailedException;
use Formotron\FormProcessor;

/**
 * Helpers for testing Formotron data objects.
 */
trait FormProcessorTestTrait
{
    protected function createFormProcessor(array $services = []): FormProcessor
    {
        $container = new Container($services);
        $formProcessor = $container->get(FormProcessor::class);

        return $formProcessor;
    }

    /**
     * @template T of object
     * @param class-string<T> $className
     * @return T
     */
    protected function processFormData(array $input, string $className, array $services = []): object
    {
        return $this->createFormProcessor($services)->process($input, $className);
    }

    protected function assertInvalidFormData(array $input, string $className, array $services = []): void
    {
        $this->expectException(AssertionFailedException::class);
        $this->processFormData($input, $className, $services);
    }
}
