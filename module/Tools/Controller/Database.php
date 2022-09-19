<?php

/**
 * Manage database schema
 *
 * Copyright (C) 2011-2022 Holger Schletz <holger.schletz@web.de>
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
use Laminas\Log\Logger;
use Laminas\Log\Writer\WriterInterface;
use Library\Filter\LogLevel as LogLevelFilter;
use Library\Validator\LogLevel as LogLevelValidator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Manage database schema
 */
class Database implements ControllerInterface
{
    protected $logger;
    protected $loglevelFilter;
    protected $loglevelValidator;
    protected $schemaManager;
    protected $writer;

    public function __construct(
        SchemaManager $schemaManager,
        Logger $logger,
        WriterInterface $writer,
        LogLevelFilter $loglevelFilter,
        LogLevelValidator $loglevelValidator
    ) {
        $this->schemaManager = $schemaManager;
        $this->logger = $logger;
        $this->writer = $writer;
        $this->loglevelFilter = $loglevelFilter;
        $this->loglevelValidator = $loglevelValidator;
    }

    public function __invoke(InputInterface $input, OutputInterface $output)
    {
        $loglevel = $input->getOption('loglevel');
        $prune = $input->getOption('prune');

        if (!$this->loglevelValidator->isValid($loglevel)) {
            $output->writeln($this->loglevelValidator->getMessages()[LogLevelValidator::LOG_LEVEL]);

            return Command::FAILURE;
        }

        $this->writer->addFilter('priority', ['priority' => $this->loglevelFilter->filter($loglevel)]);
        $this->writer->setFormatter('simple', ['format' => '%priorityName%: %message%']);

        $this->logger->addWriter($this->writer);

        $this->schemaManager->updateAll($prune);

        return Command::SUCCESS;
    }
}
