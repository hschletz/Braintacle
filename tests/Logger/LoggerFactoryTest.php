<?php

namespace Braintacle\Test\Logger;

use Braintacle\Logger\LoggerFactory;
use Laminas\Log\Logger;
use Laminas\Log\Processor\PsrPlaceholder as PsrPlaceholderProcessor;
use Laminas\Log\PsrLoggerAdapter;
use Laminas\Log\Writer\Stream as StreamWriter;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class LoggerFactoryTest extends TestCase
{
    public function testFactory()
    {
        $factory = new LoggerFactory();

        /** @var PsrLoggerAdapter */
        $logger = $factory();

        $this->assertInstanceOf(LoggerInterface::class, $logger);
        $this->assertInstanceOf(PsrLoggerAdapter::class, $logger);

        /** @var Logger */
        $laminasLogger = $logger->getLogger();
        $this->assertInstanceOf(Logger::class, $laminasLogger);

        $writers = $laminasLogger->getWriters();
        $this->assertCount(1, $writers);
        $this->assertInstanceOf(StreamWriter::class, $writers->current());

        $processors = $laminasLogger->getProcessors();
        $this->assertCount(1, $processors);
        $this->assertInstanceOf(PsrPlaceholderProcessor::class, $processors->current());
    }
}
