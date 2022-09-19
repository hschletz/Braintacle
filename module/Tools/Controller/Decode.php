<?php

/**
 * Decode a compressed inventory file
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

use Library\FileObject;
use Protocol\Filter\InventoryDecode;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Decode a compressed inventory file
 */
class Decode implements ControllerInterface
{
    protected $inventoryDecodeFilter;

    public function __construct(InventoryDecode $inventoryDecodeFilter)
    {
        $this->inventoryDecodeFilter = $inventoryDecodeFilter;
    }

    public function __invoke(InputInterface $input, OutputInterface $output)
    {
        $inputFile = $input->getArgument('input file');
        $outputFile = $input->getArgument('output file');

        if (!is_file($inputFile) or !is_readable($inputFile)) {
            $output->writeln('Input file does not exist or is not readable.');

            return 10;
        }

        try {
            $content = $this->inventoryDecodeFilter->filter(FileObject::fileGetContents($inputFile));
            if ($outputFile) {
                $filesystem = new Filesystem();
                $filesystem->dumpFile($outputFile, $content);
            } else {
                $output->write($content);
            }
        } catch (\InvalidArgumentException $e) {
            $output->writeln($e->getMessage());

            return 11;
        }

        return Command::SUCCESS;
    }
}
