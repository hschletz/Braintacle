<?php

/**
 * Send inventory data
 *
 * Copyright (C) 2011-2025 Holger Schletz <holger.schletz@web.de>
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

namespace Protocol\Message;

use Braintacle\Dom\Document;
use Model\Client\Client;
use Protocol\Message\InventoryRequest\Content;
use Protocol\Module;

/**
 * Send inventory data
 */
class InventoryRequest extends Document
{
    public function __construct(protected Content $contentPrototype)
    {
        parent::__construct();
    }

    public function getSchemaFilename(): string
    {
        return Module::getPath('data/RelaxNG/InventoryRequest.rng');
    }

    /**
     * Load document tree from a client object
     *
     * @param \Model\Client\Client $client Client data source
     */
    public function loadClient(Client $client)
    {
        // Root element
        $request = $this->createRoot('REQUEST');
        // Additional elements
        $request->appendTextNode('DEVICEID', $client['IdString']);
        $request->appendTextNode('QUERY', 'INVENTORY');
        // Main inventory section
        $content = clone $this->contentPrototype;
        $request->appendChild($content);
        $content->setClient($client);
        $content->appendSections();
    }

    /**
     * Get a proposed filename for exported XML file
     *
     * The filename is derived from the client ID and validated to be safe to
     * use (no special characters that could cause path traversal, header
     * injection etc.)
     *
     * @return string Filename with .xml extension
     * @throws \LogicException if element holding the client ID is missing
     * @throws \UnexpectedValueException if client ID contains invalid characters
     */
    public function getFilename()
    {
        $id = $this->getElementsByTagName('DEVICEID')->item(0);
        if (!$id) {
            throw new \LogicException('DEVICEID element has not been set');
        }
        $filename = $id->nodeValue;
        // Typical value is NAME-YYYY-MM-DD-HH-MM-SS, with NAME consisting of
        // ASCII letters, digits, dashes and underscores. Restrict filename to
        // the characters from this pattern.
        if (!preg_match('/^[A-Za-z0-9_-]+$/', $filename)) {
            throw new \UnexpectedValueException($filename . ' is not a valid filename part');
        }
        return $filename . '.xml';
    }
}
