<?php

/**
 * Search form
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

namespace Console\Form;

use Laminas\Form\Element;

/**
 * Search form
 *
 * The form requires the following options to be set before invoking init():
 *
 * - **translator:** Translator
 * - **registryManager:** \Model\Registry\RegistryManager instance
 * - **customFieldManager:** \Model\Client\CustomFieldManager instance
 *
 * The factory injects these automatically.
 */
class Search extends Form
{
    /**
     * All available filters with their translated labels.
     * @var string[]
     */
    protected $_filters; // Populated by init()

    /**
     * Filter types (default: text)
     *
     * Custom field types are added by init() if necessary.
     * @var string[]
     */
    protected $_types = array(
        'CpuClock' => 'integer',
        'CpuCores' => 'integer',
        'InventoryDate' => 'date',
        'LastContactDate' => 'date',
        'PhysicalMemory' => 'integer',
        'SwapMemory' => 'integer',
        'Filesystem.Size' => 'integer',
        'Filesystem.FreeSpace' => 'integer',
    );

    /**
     * Value options for ordinal searches (integer, float, date)
     * @var string[]
     */
    protected $_operatorsOrdinal = array(
        'eq' => '=',
        'ne' => '!=',
        'lt' => '<',
        'le' => '<=',
        'ge' => '>=',
        'gt' => '>',
    );

    /**
     * Value options for text searches
     * @var string[]
     */
    protected $_operatorsText; // Populated by init()

    /** {@inheritdoc} */
    public function init()
    {
        parent::init();
        $translator = $this->getOption('translator');

        $this->_filters = array(
            'Name' => $translator->translate('Name'),
            'UserName' => $translator->translate('User name'),
            'Windows.UserDomain' => $translator->translate('User domain'),
            'Windows.Workgroup' => $translator->translate('Workgroup'),
            'OsName' => $translator->translate('OS name'),
            'OsVersionNumber' => $translator->translate('OS version number'),
            'OsVersionString' => $translator->translate('OS version string'),
            'Windows.CpuArchitecture' => $translator->translate('OS architecture'),
            'OsComment' => $translator->translate('OS comment'),
            'Windows.ProductKey' => $translator->translate('Windows product key'),
            'Windows.ManualProductKey' => $translator->translate('Windows product key (manual)'),
            'Software.name' => $translator->translate('Software: Name'),
            'Software.version' => $translator->translate('Software: Version'),
            'Software.publisher' => $translator->translate('Software: Publisher'),
            'Software.comment' => $translator->translate('Software: Comment'),
            'Software.installLocation' => $translator->translate('Software: Install location'),
            'MsOfficeProduct.ProductKey' => $translator->translate('MS Office product key'),
            'MsOfficeProduct.ProductId' => $translator->translate('MS Office product ID'),
            'InventoryDate' => $translator->translate('Last inventory'),
            'LastContactDate' => $translator->translate('Last contact'),
            'CpuType' => $translator->translate('CPU type'),
            'CpuClock' => $translator->translate('CPU clock (MHz)'),
            'CpuCores' => $translator->translate('CPU cores'),
            'PhysicalMemory' => $translator->translate('Physical memory'),
            'SwapMemory' => $translator->translate('Swap memory'),
            'Manufacturer' => $translator->translate('Manufacturer'),
            'ProductName' => $translator->translate('Model'),
            'Serial' => $translator->translate('Serial number'),
            'AssetTag' => $translator->translate('Asset tag'),
            'BiosVersion' => $translator->translate('BIOS version'),
            'BiosDate' => $translator->translate('BIOS date'),
            'Filesystem.Size' => $translator->translate('Filesystem size (MB)'),
            'Filesystem.FreeSpace' => $translator->translate('Filesystem free space (MB)'),
            'DnsServer' => $translator->translate('DNS server'),
            'DefaultGateway' => $translator->translate('Default gateway'),
            'NetworkInterface.IpAddress' => $translator->translate('IP address'),
            'NetworkInterface.MacAddress' => $translator->translate('MAC address'),
            'NetworkInterface.Subnet' => $translator->translate('Network address'),
            'NetworkInterface.Netmask' => $translator->translate('Network Mask'),
            'Printer.Name' => $translator->translate('Printer name'),
            'Printer.Port' => $translator->translate('Printer port'),
            'Printer.Driver' => $translator->translate('Printer driver'),
            'UserAgent' => $translator->translate('User agent'),
            'Display.Manufacturer' => $translator->translate('Monitor: manufacturer'),
            'Display.Description' => $translator->translate('Monitor: description'),
            'Display.Serial' => $translator->translate('Monitor: serial'),
            'Display.Edid' => $translator->translate('Monitor: EDID'),
            'DisplayController.Name' => $translator->translate('Display controller'),
            'DisplayController.Memory' => $translator->translate('GPU memory'),
            'Modem.Name' => $translator->translate('Modem'),
            'AudioDevice.Name' => $translator->translate('Audio device'),
            'Port.Name' => $translator->translate('Port name'),
        );

        // Append filters and labels for registry values/data
        foreach ($this->getOption('registryManager')->getValueDefinitions() as $regValue) {
            $name = $regValue['Name'];
            $this->_filters["Registry.$name"] = "Registry: $name";
        }

        // Append filters and labels for user defined info
        $template = $translator->translate('User defined: %s');
        foreach ($this->getOption('customFieldManager')->getFields() as $name => $type) {
            $key = "CustomFields.$name";
            switch ($type) {
                case 'text':
                case 'clob':
                    break;
                case 'integer':
                case 'float':
                case 'date':
                    $this->_types[$key] = $type;
                    break;
                default:
                    throw new \UnexpectedValueException('Unsupported datatype: ' . $type);
            }
            if ($name == 'TAG') {
                $label = $translator->translate('Category');
            } else {
                $label = $name;
            }
            $this->_filters[$key] = sprintf($template, $label);
        }

        $this->_operatorsText = [
            'like' => $translator->translate("Substring match, wildcards '?' and '*' allowed"),
            'eq' => $translator->translate('Exact match'),
        ];

        $filter = new Element\Select('filter');
        $filter->setLabel('Search for')
               ->setAttribute('data-types', json_encode($this->_types))
               ->setAttribute('type', 'select_untranslated')
               ->setValueOptions($this->_filters)
               ->setValue('Name'); // Default value
        $this->add($filter);

        $search = new Element\Text('search');
        $search->setLabel('Value');
        $this->add($search);

        // Operators dropdown. Options are set by JS depending on filter type.
        // Since valid options are known only after submission, the internal
        // InArray validator must be disabled and replaced by a callback.
        $operator = new Element\Select('operator');
        $operator->setAttribute('type', 'select_untranslated')
                 ->setAttribute('data-operators-ordinal', json_encode($this->_operatorsOrdinal))
                 ->setAttribute('data-operators-text', json_encode($this->_operatorsText))
                 ->setValueOptions($this->_operatorsText) // Operators for default value "Name"
                 ->setLabel('Operator');
        $this->add($operator);

        $invert = new Element\Checkbox('invert');
        $invert->setLabel('Invert results');
        $this->add($invert);

        $submit = new \Library\Form\Element\Submit('customSearch');
        $submit->setLabel('Search');
        $this->add($submit);

        $inputFilter = new \Laminas\InputFilter\InputFilter();
        $inputFilter->add(
            array(
                'name' => 'search',
                'continue_if_empty' => true, // Have empty value processed by callback validator
                'filters' => array(
                    array(
                        'name' => 'Callback',
                        'options' => array(
                            'callback' => array($this, 'filterSearch'),
                        ),
                    ),
                ),
                'validators' => array(
                    array(
                        'name' => 'Callback',
                        'options' => array(
                            'callback' => array($this, 'validateSearch'),
                        ),
                    ),
                ),
            )
        );
        $inputFilter->add(
            array(
                'name' => 'operator',
                'validators' => array(
                    array(
                        'name' => 'Callback',
                        'options' => array(
                            'callback' => array($this, 'validateOperator'),
                        ),
                    ),
                ),
            )
        );
        $this->setInputFilter($inputFilter);
    }

    /**
     * Filter callback for search input
     *
     * @internal
     * @param string $value
     * @return mixed normalized input depending on filter type
     */
    public function filterSearch($value)
    {
        return $this->normalize(
            $value,
            $this->getTypeFromFilter($this->get('filter')->getValue())
        );
    }

    /**
     * Validator callback for search input
     *
     * @internal
     * @param string $value
     * @param array $context
     * @return bool TRUE if $value is a valid string/integer/float/date
     * @throws \LogicException if context does not contain filter
     */
    public function validateSearch($value, $context)
    {
        if (!isset($context['filter'])) {
            throw new \LogicException('No filter submitted');
        }
        return $this->validateType($value, $context, $this->getTypeFromFilter($context['filter']));
    }

    /**
     * Validator callback for operator input
     *
     * @internal
     * @param string $value
     * @param array $context
     * @return bool TRUE if $value is valid for the selected filter type
     * @throws \LogicException if context does not contain filter
     */
    public function validateOperator($value, $context)
    {
        if (!isset($context['filter'])) {
            throw new \LogicException('No filter submitted');
        }
        if ($this->getTypeFromFilter($context['filter']) == 'text') {
            $operators = $this->_operatorsText;
        } else {
            $operators = $this->_operatorsOrdinal;
        }
        return isset($operators[$value]);
    }

    /** {@inheritdoc} */
    public function setData($data)
    {
        $type = $this->getTypeFromFilter($data['filter']);
        $data['search'] = $this->localize(@$data['search'], $type);
        $this->get('operator')->setValueOptions(($type == 'text') ? $this->_operatorsText : $this->_operatorsOrdinal);

        return parent::setData($data);
    }

    /**
     * Get the datatype for a specific filter
     *
     * @param string $filter Filter name
     * @return string datatype
     * @throws \InvalidArgumentException if the filter name is invalid
     **/
    protected function getTypeFromFilter($filter)
    {
        if (!isset($this->_filters[$filter])) {
            throw new \InvalidArgumentException('Invalid filter: ' . $filter);
        }

        if (isset($this->_types[$filter])) {
            return $this->_types[$filter];
        } else {
            return 'text';
        }
    }
}
