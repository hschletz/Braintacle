<?php
/**
 * Export controller
 *
 * Copyright (C) 2011-2016 Holger Schletz <holger.schletz@web.de>
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
 * Export controller
 */
class Export
{
    /**
     * Client manager
     * @var \Model\Client\ClientManager
     */
    protected $_clientManager;

    /**
     * Constructor
     *
     * @param \Model\Client\ClientManager $clientManager
     */
    public function __construct(\Model\Client\ClientManager $clientManager)
    {
        $this->_clientManager = $clientManager;
    }

    /**
     * Export all clients
     *
     * @param \ZF\Console\Route $route
     * @param \Zend\Console\Adapter\AdapterInterface $console
     * @return integer Exit code
     */
    public function __invoke(\ZF\Console\Route $route, \Zend\Console\Adapter\AdapterInterface $console)
    {
        $directory = $route->getMatchedParam('directory');
        $validate = $route->getMatchedParam('validate') || $route->getMatchedParam('v');

        if (!is_dir($directory) or !is_writable($directory)) {
            $console->writeLine("Directory '$directory' does not exist or is not writable.");
            return 10;
        }

        if ($validate) {
            ini_set('display_errors', true); // Print reason for validation failure
            ini_set('log_errors', false); // Prevent duplicate message in case of validation failure
            if (extension_loaded('xdebug')) {
                // Prevent printing backtraces on validation errors
                xdebug_disable();
            }
        }

        $clients = $this->_clientManager->getClients(null, 'IdString');
        foreach ($clients as $client) {
            $id = $client['IdString'];
            $console->writeLine("Exporting $id");
            $document = $client->toDomDocument();
            $document->save($directory . '/' . $document->getFilename());
            if ($validate and !$document->isValid()) {
                $console->writeLine("Validation failed for $id.");
                return 11;
            }
        }
        return 0;
    }
}
