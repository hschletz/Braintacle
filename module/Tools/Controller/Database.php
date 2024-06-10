<?php

/**
 * Manage database schema
 *
 * Copyright (C) 2011-2024 Holger Schletz <holger.schletz@web.de>
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option)
 * any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for
 * more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

namespace Tools\Controller;

use Database\SchemaManager;
use Laminas\Log\Filter\Priority as PriorityFilter;
use Laminas\Log\Formatter\Simple as SimpleFormatter;
use Laminas\Log\Logger;
use Laminas\Log\PsrLoggerAdapter;
use Laminas\Log\Writer\WriterInterface;
use Library\Filter\LogLevel as LogLevelFilter;
use Library\Validator\LogLevel as LogLevelValidator;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Manage database schema
 */
class Database implements ControllerInterface
{
    public function __construct(
        private SchemaManager $schemaManager,
        private LoggerInterface $logger,
        private LogLevelFilter $loglevelFilter,
        private LogLevelValidator $loglevelValidator
    ) {
    }

    public function __invoke(InputInterface $input, OutputInterface $output)
    {
        $loglevel = $input->getOption('loglevel');
        $prune = $input->getOption('prune');

        if (!$this->loglevelValidator->isValid($loglevel)) {
            $output->writeln($this->loglevelValidator->getMessages()[LogLevelValidator::LOG_LEVEL]);

            return Command::FAILURE;
        }

        // Assume logger as set up during container initialization.
        assert($this->logger instanceof PsrLoggerAdapter);
        $logger = $this->logger->getLogger();
        assert($logger instanceof Logger);
        $loglevel = $this->loglevelFilter->filter($loglevel);

        // Clone the writers queue because it's an SplPriorityQueue which will
        // be emptied upon iteration. Cloning is shallow; the writer objects
        // will be the same instances, and the modifications will apply to the
        // original queue.
        /** @var WriterInterface $writer */
        foreach (clone $logger->getWriters() as $writer) {
            $writer->addFilter(new PriorityFilter($loglevel));
            $writer->setFormatter(new SimpleFormatter('%priorityName%: %message%'));
        }

        $this->schemaManager->updateAll($prune);

        return Command::SUCCESS;
    }
}
