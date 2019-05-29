<?php
/**
 * Decode controller
 *
 * Copyright (C) 2011-2019 Holger Schletz <holger.schletz@web.de>
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

/**
 * Decode controller
 */
class Decode
{
    /**
     * Filter instance
     * @var \Protocol\Filter\InventoryDecode
     */
    protected $_inventoryDecode;

    /**
     * Constructor
     *
     * @param \Protocol\Filter\InventoryDecode $inventoryDecode
     */
    public function __construct(\Protocol\Filter\InventoryDecode $inventoryDecode)
    {
        $this->_inventoryDecode = $inventoryDecode;
    }

    /**
     * Decode a compressed inventory file
     *
     * @param \ZF\Console\Route $route
     * @param \Zend\Console\Adapter\AdapterInterface $console
     * @return integer Exit code
     */
    public function __invoke(\ZF\Console\Route $route, \Zend\Console\Adapter\AdapterInterface $console)
    {
        $input = $route->getMatchedParam('input_file');
        $output = $route->getMatchedParam('output_file');

        if (!is_file($input) or !is_readable($input)) {
            $console->writeLine('Input file does not exist or is not readable.');
            return 10;
        }

        try {
            $content = $this->_inventoryDecode->filter(\Library\FileObject::fileGetContents($input));
            if ($output) {
                $fileSystem = new \Symfony\Component\Filesystem\Filesystem;
                $fileSystem->dumpFile($output, $content);
            } else {
                $console->write($content);
            }
            return 0;
        } catch (\InvalidArgumentException $e) {
            $console->writeLine($e->getMessage());
            return 11;
        }
    }
}
