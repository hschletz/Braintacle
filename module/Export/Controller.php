<?php
/**
 * Export application controller
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

namespace Export;

/**
 * Export application controller
 */
class Controller extends \Zend\Mvc\Controller\AbstractConsoleController
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
     */
    public function exportAction()
    {
        $request = $this->getRequest();
        $directory = $request->getParam('directory');
        $validate = $request->getParam('validate') || $request->getParam('v');

        if (!is_dir($directory) or !is_writable($directory)) {
            $model = new \Zend\View\Model\ConsoleModel;
            $model->setErrorLevel(10);
            $model->setResult("Directory '$directory' does not exist or is not writable.\n");
            return $model;
        }

        $clients = $this->_clientManager->getClients(
            null,
            'IdString'
        );
        foreach ($clients as $client) {
            $id = $client['IdString'];
            $this->console->writeLine("Exporting $id");
            $document = $client->toDomDocument();
            $document->save($directory . '/' . $document->getFilename());
            if ($validate and !$document->isValid()) {
                $model = new \Zend\View\Model\ConsoleModel;
                $model->setErrorLevel(11);
                $model->setResult("Validation failed for $id.\n");
                return $model;
            }
        }
    }
}
