<?php

namespace Braintacle\Logger;

use Laminas\Log\Formatter\Simple as SimpleFormatter;
use Laminas\Log\Logger;
use Laminas\Log\Processor\PsrPlaceholder as PsrPlaceholderProcessor;
use Laminas\Log\PsrLoggerAdapter;
use Laminas\Log\Writer\Stream as StreamWriter;
use Psr\Log\LoggerInterface;

class LoggerFactory
{
    public function __invoke(): LoggerInterface
    {
        $writer = new StreamWriter('php://stderr');
        $writer->setFormatter(new SimpleFormatter('%timestamp% Braintacle %priorityName%: %message% %extra%'));

        $logger = new Logger();
        $logger->addProcessor(new PsrPlaceholderProcessor());
        $logger->addWriter($writer);

        return new PsrLoggerAdapter($logger);
    }
}
