<?php

/**
 * Export all clients
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

use Model\Client\ClientManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Export all clients
 */
class Export implements ControllerInterface
{
    protected $clientManager;

    public function __construct(ClientManager $clientManager)
    {
        $this->clientManager = $clientManager;
    }

    public function __invoke(InputInterface $input, OutputInterface $output)
    {
        $directory = $input->getArgument('directory');
        $validate = $input->getOption('validate');

        if (!is_dir($directory) or !is_writable($directory)) {
            $output->writeln("Directory '$directory' does not exist or is not writable.");

            return 10;
        }

        if ($validate) {
            ini_set('display_errors', true); // Print reason for validation failure
            ini_set('log_errors', 0); // Prevent duplicate message in case of validation failure
        }

        /** @var \Model\Client\Client[] */
        $clients = $this->clientManager->getClients(null, 'IdString');
        foreach ($clients as $client) {
            $id = $client['IdString'];
            $output->writeln("Exporting $id");
            $document = $client->toDomDocument();
            $document->write($directory . '/' . $document->getFilename());
            if ($validate and !$document->isValid()) {
                $output->writeln("Validation failed for $id.");

                return 11;
            }
        }

        return Command::SUCCESS;
    }
}
