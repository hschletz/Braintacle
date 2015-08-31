<?php
/**
 * Send inventory data
 *
 * Copyright (C) 2011-2015 Holger Schletz <holger.schletz@web.de>
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
        'extensionslot' => 'SLOTS',
        'software' => 'SOFTWARES',
        'audiodevice' => 'SOUNDS',
        'storagedevice' => 'STORAGES',
        'displaycontroller' => 'VIDEOS',
        'virtualmachine' => 'VIRTUALMACHINES',
    );

    /**
     * Global cache for element=>model mappings
     * @var string[]
     * @deprecated Query ItemManager for non-hardcoded elements
     */
    private $_models;

    /**
     * Global cache for element=>property mappings
     * @var array
     * @deprecated Retrieve child elements via hydrator
     */
    private $_properties;

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
    )
    {
        $this->_parseSchema();
        $itemManager = $serviceLocator->get('Model\Client\ItemManager');

        // Root element
        $request = $this->createElement('REQUEST');
        $this->appendChild($request);
        // Additional elements
        $request->appendChild($this->createElementWithContent('DEVICEID', $client['ClientId']));
        $request->appendChild($this->createElement('QUERY', 'INVENTORY'));
        // Main inventory section
        $content = $this->createElement('CONTENT');
        $request->appendChild($content);

        foreach (array('HARDWARE', 'BIOS') as $section) {
            $element = $this->createElement($section);
            foreach ($this->_properties[$section] as $name => $property) {
                if ($this->_models[$section][$name] == 'WindowsInstallation') {
                    $value = $client['Windows'][$property];
                } elseif ($property == 'InventoryDate' or $property == 'LastContactDate') {
                    $value = $client[$property]->format('Y-m-d H:i:s');
                } else {
                    $value = $client[$property];
                }
                if ((string) $value != '') {
                    $element->appendChild($this->createElementWithContent($name, $value));
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
                $element = $this->createElement('ACCOUNTINFO');
                $element->appendChild(
                    $this->createElementWithContent('KEYNAME', $property)
                );
                $element->appendChild(
                    $this->createElementWithContent('KEYVALUE', $value)
                );
                $content->appendChild($element);
            }
        }
        // DOWNLOAD section
        $packages = $client->getDownloadedPackages();
        if ($packages) {
            // DOWNLOAD section has 1 HISTORY element with 1 PACKAGE element per package.
            $download = $this->createElement('DOWNLOAD');
            $content->appendChild($download);
            $history = $this->createElement('HISTORY');
            $download->appendChild($history);
            foreach ($packages as $id) {
                $package = $this->createElement('PACKAGE');
                $package->setAttribute('ID', $id);
                $history->appendChild($package);
            }
        }
        // Item sections
        foreach (self::$_itemSections as $type => $section) {
            $items = $client->getItems($type, 'id', 'asc');
            if ($items) {
                $table = $itemManager->getTableName($type);
                $hydrator = $serviceLocator->get("Protocol\\Hydrator\\$table");
                foreach ($items as $object) {
                    $element = $this->createElement($section);
                    foreach ($hydrator->extract($object) as $name => $value) {
                        if ((string) $value != '') {
                            $element->appendChild($this->createElementWithContent($name, $value));
                        }
                    }
                    $content->appendChild($element);
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

    /**
     * Extract element=>model/property mappings from schema
     * @deprecated Use alternative mechanisms in favour of $_models and $_properties
     */
    private function _parseSchema()
    {
        $this->_models = array();
        $this->_properties = array();

        $filename = $this->getSchemaFilename();
        $schema = new \Library\DomDocument;
        $schema->load($filename);
        $xpath = new \DOMXPath($schema);

        // Extract all elements having a braintacle:model attribute
        $models = $xpath->query('//*[@braintacle:model]');
        foreach ($models as $item) {
            if ($item->hasAttribute('braintacle:property')) {
                // $item is a child element with overridden model attribute. It
                // gets evaluated later. For now, only elements representing an
                // entire section are relevant.
                continue;
            }
            $section = $item->getAttribute('name');
            $model = $item->getAttribute('braintacle:model');
            // Extract all child elements having a braintacle:property attribute
            $properties = $xpath->query('.//*[@braintacle:property]', $item);
            foreach ($properties as $item) {
                if ($item->hasAttribute('braintacle:model')) {
                    // Child element has overridden model attribute
                    $elementModel = $item->getAttribute('braintacle:model');
                } else {
                    // Inherit model attribute from section
                    $elementModel = $model;
                }
                $property = $item->getAttribute('braintacle:property');
                // Store mappings in cache
                $element = $item->getAttribute('name');
                $this->_models[$section][$element] = $elementModel;
                $this->_properties[$section][$element] = $property;
            }
            // If no properties are defined, store just the model.
            if (!isset($this->_models[$section])) {
                $this->_models[$section] = $model;
            }
        }
    }
}
