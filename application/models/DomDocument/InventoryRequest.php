<?php
/**
 * Interface to inventory XML documents
 *
 * $Id$
 *
 * Copyright (C) 2011-2013 Holger Schletz <holger.schletz@web.de>
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
 *
 * @package Models
 */
/**
 * Interface to inventory XML documents
 * @package Models
 */
class Model_DomDocument_InventoryRequest extends Model_DomDocument
{
    /**
     * Global cache for element=>model mappings
     *
     * Do not use directly, always call {@link getModels} to retrieve map
     * @var array
     */
    private static $_models;

    /**
     * Global cache for element=>property mappings
     *
     * Do not use directly, always call {@link getProperties} to retrieve map
     * for a specific section
     * @var array
     */
    private  static $_properties;

    /**
     * Global cache for model=>property=>format specification
     *
     * Do not use directly, always call {@link getFormat()} to retrieve format for
     * a specific property
     * @var array
     */
    private static $_formats;

    /**
     * Load document tree from a computer
     * @param Model_Computer $computer Computer whose data will be exported
     */
    public function loadComputer(Model_Computer $computer)
    {
        // Always export all software regardless of DisplayBlacklistedSoftware
        // option. Preserve old value and temporarily override it.
        $displayBlacklistedSoftware = Model_Config::get('DisplayBlacklistedSoftware');
        Model_Config::set('DisplayBlacklistedSoftware', true, false);

        // Collect all sections in an array
        $sections = array();
        foreach ($this->getModels() as $section => $models) {
            switch ($section) {
                case 'HARDWARE':
                case 'BIOS':
                    $element = $this->createElement($section);
                    foreach ($this->getProperties($section) as $name => $property) {
                        // Get raw value from model class
                        if ($models[$name] == 'Windows') {
                            $property = $computer->getWindows()->getProperty($property, true);
                        } else {
                            $property = $computer->getProperty($property, true);
                        }
                        if (strlen($property)) {// Don't generate empty elements
                            $element->appendChild(
                                $this->createElementWithContent($name, $property)
                            );
                        }
                    }
                    $sections[$section] = $element;
                    break;
                case 'ACCOUNTINFO':
                    $sections['ACCOUNTINFO'] = $this->createDocumentFragment();
                    // Although not strictly necessary, sort entries to simplify
                    // comparision of results.
                    $info = array();
                    foreach ($computer->getUserDefinedInfo() as $property => $value) {
                        // Convert date values
                        if ($value instanceof Zend_Date) {
                            $value = $value->get('yyyy-MM-dd');
                        }
                        $info[$property] = $value;
                    }
                    ksort($info);
                    foreach ($info as $property => $value) {
                        if (strlen($value)) { // Don't generate empty elements
                            $element = $this->createElement('ACCOUNTINFO');
                            $element->appendChild(
                                $this->createElementWithContent('KEYNAME', $property)
                            );
                            $element->appendChild(
                                $this->createElementWithContent('KEYVALUE', $value)
                            );
                            $sections['ACCOUNTINFO']->appendChild($element);
                        }
                    }
                    break;
                case 'DOWNLOAD':
                    // DOWNLOAD section has 1 HISTORY element with 1 PACKAGE child per package.
                    $history = $this->createElement('HISTORY');
                    foreach ($computer->getDownloadedPackages() as $id) {
                        $package = $this->createElement('PACKAGE');
                        $package->setAttribute('ID', $id);
                        $history->appendChild($package);
                    }
                    $sections['DOWNLOAD'] = $this->createElement('DOWNLOAD');
                    if ($history->hasChildNodes()) { // Don't append empty HISTORY
                        $sections['DOWNLOAD']->appendChild($history);
                    }
                    break;
                default:
                    $sections[$section] = $this->createDocumentFragment();
                    // Fetch data from child objects, once per distinct model
                    foreach (array_unique($models) as $model) {
                        $statement = $computer->getChildObjects(
                            $model,
                            'id', // Sort by 'id' to get more predictable results for comparision
                            'asc'
                        );
                        while ($object = $statement->fetchObject('Model_' . $model)) {
                            // Create base element
                            $element = $this->createElement($section);
                            // Create child elements, 1 per property
                            foreach ($this->getProperties($section) as $name => $property) {
                                $value = $object->getProperty($property, true); // Get raw value
                                if (strlen($value)) { // Don't generate empty elements
                                    $type = $object->getPropertyType($property);
                                    if ($type == 'timestamp' or $type == 'date') {
                                        // Re-fetch value as Zend_Date
                                        $value = $object->getProperty($property, false);
                                        // Convert to specific format
                                        $value = $value->get($this->getFormat($model, $property));
                                    }
                                    $element->appendChild(
                                        $this->createElementWithContent($name, $value)
                                    );
                                }
                            }
                            $sections[$section]->appendChild($element);
                        }
                    }
                    break;
            }
        }

        // Root element
        $request = $this->createElement('REQUEST');
        $this->appendChild($request);

        // Main inventory section
        $content = $this->createElement('CONTENT');
        $request->appendChild($content);
        foreach ($sections as $fragment) {
            if ($fragment->hasChildNodes()) { // Ignore empty fragments
                $content->appendChild($fragment);
            }
        }

        // Additional elements
        $request->appendChild($this->createElementWithContent('DEVICEID', $computer->getClientId()));
        $request->appendChild($this->createElement('QUERY', 'INVENTORY'));

        // Restore option
        Model_Config::set('DisplayBlacklistedSoftware', $displayBlacklistedSoftware, false);
    }

    /**
     * Get a proposed filename for exported XML file
     *
     * The filename is derived from the computer's client ID and validated to be
     * safe to use (no special characters that could cause path traversal,
     * header injection etc.)
     * @return string Filename with .xml extension
     */
    public function getFilename()
    {
        $filename = $this->getElementsByTagName('DEVICEID')->item(0);
        if (!$filename) {
            throw new RuntimeException('DEVICEID element has not been set');
        }
        $filename = $filename->nodeValue;
        if (!is_string($filename)) {
            throw new RuntimeException('DEVICEID element has invalid content');
        }
        // The value comes from an untrusted source and must be validated. The
        // most universally safe constraint is a strict NAME-YYYY-MM-DD-HH-MM-SS
        // pattern with NAME consisting of letters, digits, dashes and
        // underscores.
        if (!preg_match('/^[A-Za-z0-9_-]+-\d\d\d\d-\d\d-\d\d-\d\d-\d\d-\d\d$/', $filename)) {
            throw new UnexpectedValueException($filename . ' is not a valid filename part');
        }
        return $filename . '.xml';
    }

    /**
     * Retrieve array of section=>element=>model mappings
     * @return array
     */
    public function getModels()
    {
        if (empty(self::$_models)) {
            $this->_parseSchema();
        }
        return self::$_models;
    }

    /**
     * Retrieve array of element=>property mappings for a given section
     * @param string $section Inventory section to evaluate
     * @return array
     */
    public function getProperties($section)
    {
        if (empty(self::$_properties)) {
            $this->_parseSchema();
        }
        return self::$_properties[$section];
    }

    /**
     * Retrieve date/timestamp format for a given property
     * @param string $model Model class (without 'Model_' prefix)
     * @param string $property Property whose format to retrieve
     * @return string Format string
     */
    public function getFormat($model, $property)
    {
        if (empty(self::$_formats)) {
            $this->_parseSchema();
        }
        return self::$_formats[$model][$property];
    }

    /**
     * Extract element=>model/property mappings from schema
     *
     * The mappings are cached globally so that this has to be done only once.
     */
    private function _parseSchema()
    {
        self::$_models = array();
        self::$_properties = array();

        $filename = $this->getSchemaFilename();
        $schema = new DOMDocument;
        if (!$schema->load($filename)) {
            throw new RuntimeException('Unable to load/parse ' . $filename);
        }
        $xpath = new DOMXPath($schema);

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
                self::$_models[$section][$element] = $elementModel;
                self::$_properties[$section][$element] = $property;
                // Store date/timestamp format in cache if specified
                $format = $item->getAttribute('braintacle:format');
                if ($format) {
                    self::$_formats[$elementModel][$property] = $format;
                }
            }
            // If no properties are defined, store just the model.
            if (!isset(self::$_models[$section])) {
                self::$_models[$section] = $model;
            }
        }
    }
}
