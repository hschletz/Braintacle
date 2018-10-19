<?php
/**
 * Send inventory data
 *
 * Copyright (C) 2011-2018 Holger Schletz <holger.schletz@web.de>
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

/**
 * Send inventory data
 */
class InventoryRequest extends \Library\DomDocument
{
    /**
     * Map of item types to section names
     * @var string[]
     */
    protected static $_itemSections = array(
        'controller' => 'CONTROLLERS',
        'cpu' => 'CPUS',
        'filesystem' => 'DRIVES',
        'inputdevice' => 'INPUTS',
        'memoryslot' => 'MEMORIES',
        'modem' => 'MODEMS',
        'display' => 'MONITORS',
        'networkinterface' => 'NETWORKS',
        'msofficeproduct' => 'OFFICEPACK',
        'port' => 'PORTS',
        'printer' => 'PRINTERS',
        'registrydata' => 'REGISTRY',
        'sim' => 'SIM',
        'extensionslot' => 'SLOTS',
        'software' => 'SOFTWARES',
        'audiodevice' => 'SOUNDS',
        'storagedevice' => 'STORAGES',
        'displaycontroller' => 'VIDEOS',
        'virtualmachine' => 'VIRTUALMACHINES',
    );

    /** {@inheritdoc} */
    public function getSchemaFilename()
    {
        return \Protocol\Module::getPath('data/RelaxNG/InventoryRequest.rng');
    }

    /**
     * Load document tree from a client object
     *
     * @param \Model\Client\Client $client Client data source
     * @param \Zend\ServiceManager\ServiceLocatorInterface $serviceLocator Service manager
     */
    public function loadClient(
        \Model\Client\Client $client,
        \Zend\ServiceManager\ServiceLocatorInterface $serviceLocator
    ) {
        $itemManager = $serviceLocator->get('Model\Client\ItemManager');

        // Root element
        $request = $this->appendElement('REQUEST');
        // Additional elements
        $request->appendElement('DEVICEID', $client['IdString'], true);
        $request->appendElement('QUERY', 'INVENTORY');
        // Main inventory section
        $content = $request->appendElement('CONTENT');

        $sections = array('HARDWARE' => 'ClientsHardware', 'BIOS' => 'ClientsBios');
        foreach ($sections as $section => $hydratorName) {
            $data = $serviceLocator->get("Protocol\Hydrator\\$hydratorName")->extract($client);
            ksort($data);
            $element = $this->createElement($section);
            foreach ($data as $name => $value) {
                if ((string) $value != '') {
                    $element->appendElement($name, $value, true);
                }
            }
            if ($element->hasChildNodes()) {
                $content->appendChild($element);
            }
        }
        // ACCOUNTINFO section
        foreach ($client['CustomFields'] as $property => $value) {
            if ($value instanceof \DateTime) {
                $value = $value->format('Y-m-d');
            }
            if ((string) $value != '') {
                $element = $content->appendElement('ACCOUNTINFO');
                $element->appendElement('KEYNAME', $property, true);
                $element->appendElement('KEYVALUE', $value, true);
            }
        }
        // DOWNLOAD section
        $packages = $client->getDownloadedPackageIds();
        if ($packages) {
            // DOWNLOAD section has 1 HISTORY element with 1 PACKAGE element per package.
            $download = $content->appendElement('DOWNLOAD');
            $history = $download->appendElement('HISTORY');
            foreach ($packages as $id) {
                $package = $history->appendElement('PACKAGE');
                $package->setAttribute('ID', $id);
            }
        }
        // Item sections
        foreach (self::$_itemSections as $type => $section) {
            $items = $client->getItems($type, 'id', 'asc');
            if ($items) {
                $table = $itemManager->getTableName($type);
                $hydrator = $serviceLocator->get("Protocol\\Hydrator\\$table");
                foreach ($items as $object) {
                    $element = $content->appendElement($section);
                    foreach ($hydrator->extract($object) as $name => $value) {
                        if ((string) $value != '') {
                            $element->appendElement($name, $value, true);
                        }
                    }
                }
            }
        }
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
