<?php

namespace Braintacle\Test;

use DI\Container;
use Formotron\DataProcessor;
use InvalidArgumentException;

/**
 * Helpers for testing Formotron data objects.
 */
trait DataProcessorTestTrait
{
    protected function createDataProcessor(array $services = []): DataProcessor
    {
        $container = new Container($services);
        $dataProcessor = $container->get(DataProcessor::class);

        return $dataProcessor;
    }

    /**
     * @template T of object
     * @param class-string<T> $className
     * @return T
     */
    protected function processData(array $input, string $className, array $services = []): object
    {
        return $this->createDataProcessor($services)->process($input, $className);
    }

    protected function assertInvalidFormData(array $input, string $className, array $services = []): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->processData($input, $className, $services);
    }
}
