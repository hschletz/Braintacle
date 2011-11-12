<?php
/**
 * Interface to inventory XML documents
 *
 * $Id$
 *
 * Copyright (C) 2011 Holger Schletz <holger.schletz@web.de>
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
 * @filesource
 */
/**
 * Interface to inventory XML documents
 * @package Models
 */
class Model_DomDocument_InventoryRequest extends Model_DomDocument
{

    /**
     * Map of valid XML elements
     *
     * Each element has an element name as the key. The value is one of:
     * - NULL if the element is unsupported or has more complex processing rules
     * - A string with a child object type. Processing is passed to the child object.
     * - An array with element=>property pairs which are handled by Model_Computer.
     * @var array
     */
    protected $_elementMap = array(
        'ACCESSLOG' => null, // obsolete
        'ACCOUNTINFO' => null, // needs more complex processing
        'BIOS' => array(
            'ASSETTAG' => 'AssetTag',
            'BDATE' => 'BiosDate',
            'BMANUFACTURER' => 'BiosManufacturer',
            'BVERSION' => 'BiosVersion',
            'SMANUFACTURER' => 'Manufacturer',
            'SMODEL' => 'Model',
            'SSN' => 'Serial',
            'TYPE' => 'Type',
        ),
        'CONTROLLERS' => 'Controller',
        'CPUS' => null, // not available in database
        'DOWNLOAD' => null, // needs more complex processing
        'DRIVES' => 'Volume',
        'HARDWARE' => array(
            'ARCH' => null, // not available in database
            'ARCHNAME' => null, // not available in database
            'CHECKSUM' => 'InventoryDiff',
            'DATELASTLOGGEDUSER' => null, // not available in database
            'DEFAULTGATEWAY' => 'DefaultGateway',
            'DESCRIPTION' => 'OsComment',
            'DNS' => 'DnsServer',
            'ETIME' => null, // obsolete
            'IPADDR' => 'IpAddress',
            'LASTCOME' => 'LastContactDate',
            'LASTDATE' => 'InventoryDate',
            'LASTLOGGEDUSER' => null, // not available in database
            'MEMORY' => 'PhysicalMemory',
            'NAME' => 'Name',
            'OSCOMMENTS' => 'OsVersionString',
            'OSNAME' => 'OsName',
            'OSVERSION' => 'OsVersionNumber',
            'PROCESSORN' => 'CpuCores',
            'PROCESSORS' => 'CpuClock',
            'PROCESSORT' => 'CpuType',
            'SWAP' => 'SwapMemory',
            'TYPE' => 'RawType',
            'USERDOMAIN' => 'UserDomain',
            'USERID' => 'UserName',
            'UUID' => 'Uuid',
            'VMSYSTEM' => null, // not available in database
            'WINCOMPANY' => 'WindowsCompany',
            'WINOWNER' => 'WindowsOwner',
            'WINPRODID' => 'WindowsProductId',
            'WINPRODKEY' => 'WindowsProductkey',
            'WORKGROUP' => 'Workgroup',
        ),
        'INPUTS' => 'InputDevice',
        'MEMORIES' => 'MemorySlot',
        'MODEMS' => 'Modem',
        'MONITORS' => 'Display',
        'NETWORKS' => 'NetworkInterface',
        'PORTS' => 'Port',
        'PRINTERS' => 'Printer',
        'PROCESSES' => null, // not available in database
        'REGISTRY' => 'Registry',
        'SLOTS' => 'ExtensionSlot',
        'SOFTWARES' => 'Software',
        'SOUNDS' => 'AudioDevice',
        'STORAGES' => 'StorageDevice',
        'USERS' => null, // not available in database
        'VIDEOS' => 'DisplayController',
        'VIRTUALMACHINES' => 'VirtualMachine',
    );

    /**
     * Load document tree from a computer
     * @param Model_Computer $computer Computer whose data will be exported
     */
    public function loadComputer(Model_Computer $computer)
    {
        // Although the order of elements is irrelevant, at least the UNIX agent
        // sorts them lexically. To simplify comparision between agent-generated
        // XML and the output of this method, the sections are collected in an
        // array first and then sorted before insertion into the document.
        $sections = array();
        foreach ($this->_elementMap as $section => $data) {
            switch ($section) {
                case 'HARDWARE':
                case 'BIOS':
                    $sections[$section] = $this->_getSectionFromComputer($section, $computer);
                    break;
                case 'ACCOUNTINFO':
                    $sections['ACCOUNTINFO'] = $this->createDocumentFragment();
                    $info = array();
                    foreach ($computer->getUserDefinedInfo() as $property => $value) {
                        $info[$property] = $value;
                    }
                    ksort($info);
                    foreach ($info as $property => $value) {
                        if (!empty($value)) { // Don't generate empty elements
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
                    if (empty($data)) {
                        continue; // Skip obsolete/unused elements
                    }
                    // $data is the type of the child object that handles the conversion
                    $statement = $computer->getChildObjects(
                        $data,
                        'id', // Sort by 'id' to get more predictable results for comparision
                        'asc'
                    );
                    $sections[$section] = $this->createDocumentFragment();
                    while ($object = $statement->fetchObject('Model_' . $data)) {
                        $sections[$section]->appendChild($object->toDomElement($this));
                    }
                    break;
            }
        }
        ksort($sections);

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
    }

    /**
     * Get HARDWARE or BIOS section
     * @param string $section Section name ('HARDWARE' or 'BIOS')
     * @param Model_Computer $computer Data source
     * @return DOMElement
     */
    protected function _getSectionFromComputer($section, $computer)
    {
        $element = $this->createElement($section);
        foreach ($this->_elementMap[$section] as $name => $property) {
            if (!$property) {
                continue; // Don't create empty elements
            }
            $element->appendChild(
                $this->createElementWithContent(
                    $name,
                    $computer->getProperty($property, true) // Get raw value
                )
            );
        }
        return $element;
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

}
