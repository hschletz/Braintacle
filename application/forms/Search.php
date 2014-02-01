<?php
/**
 * Search form.
 *
 * Copyright (C) 2011-2014 Holger Schletz <holger.schletz@web.de>
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
 * @package Forms
 */
/**
 * Search form.
 *
 * NOTE: validation of the 'search' element depends on the selected filter which
 * is not known when the form is created, but only immediately before validation.
 * For this reason, {@link setSearchOptions()} MUST be called before any
 * validation method to get correct results. This is not necessary for
 * {@link isValid()} which already does this.
 * @package Forms
 */
class Form_Search extends Form_Normalized
{

    /**
     * All available filters with their translated labels.
     * @var array
     */
    protected $_filters; // Populated by init()

    /**
     * Properties for which to perform an integer search
     * @var array
     */
    protected $_typeInteger = array(
        'CpuClock',
        'CpuCores',
        'PhysicalMemory',
        'SwapMemory',
        'Volume.Size',
        'Volume.FreeSpace',
    );

    /**
     * Properties for which to perform a float search
     * @var array
     */
    protected $_typeFloat = array(); // May be populated with user defined fields

    /**
     * Properties for which to perform a timestamp search
     * @var array
     */
    protected $_typeDate = array(
        'InventoryDate',
        'LastContactDate',
    );

    /**
     * Retrieve the datatype of an element
     */
    public function getType($name)
    {
        if ($name == 'search') {
            return $this->_getTypeFromFilter();
        } else {
            return 'text';
        }
    }

    /**
     * Get the datatype for a specific filter
     *
     * If $filter is ommitted or empty, the value of the 'filter' element is
     * used which must not be empty in that case.
     *
     * @param string $filter Filter name (default: autodetect)
     * @return string datatype
     * @throws LogicException if the 'filter' Element is evaluated but has no value
     * @throws LogicException if the filter name is invalid
     **/
    protected function _getTypeFromFilter($filter=null)
    {
        if (!$filter) {
            $filter = $this->getValue('filter');
            if (!$filter) {
                throw new LogicException('Filter element has no value.');
            }
        }
        if (!isset($this->_filters[$filter])) {
            throw new UnexpectedValueException('Invalid filter: ' . $filter);
        }


        if (in_array($filter, $this->_typeInteger)) {
             return 'integer';
        }
        if (in_array($filter, $this->_typeFloat)) {
            return 'float';
        }
        if (in_array($filter, $this->_typeDate)) {
            return 'date';
        }
        return 'text';
    }

    /**
     * Build form elements
     */
    public function init()
    {
        $this->setMethod('post');

        $translate = Zend_Registry::get('Zend_Translate');

        $this->_filters = array(
            'Name' => $translate->_('Computer name'),
            'UserName' => $translate->_('User name'),
            'Windows.UserDomain' => $translate->_('User domain'),
            'Workgroup' => $translate->_('Workgroup'),
            'OsName' => $translate->_('OS name'),
            'OsVersionNumber' => $translate->_('OS version number'),
            'OsVersionString' => $translate->_('OS version string'),
            'OsComment' => $translate->_('OS comment'),
            'Windows.ProductKey' => $translate->_('Windows product key'),
            'Windows.ManualProductKey' => $translate->_('Windows product key (manual)'),
            'Software.Name' => $translate->_('Software: Name'),
            'Software.Version' => $translate->_('Software: Version'),
            'Software.Publisher' => $translate->_('Software: Publisher'),
            'Software.Comment' => $translate->_('Software: Comment'),
            'Software.InstallLocation' => $translate->_('Software: Install location'),
            'MsOfficeProduct.ProductKey' => $translate->_('MS Office product key'),
            'MsOfficeProduct.ProductId' => $translate->_('MS Office product ID'),
            'InventoryDate' => $translate->_('Last inventory'),
            'LastContactDate' => $translate->_('Last contact'),
            'CpuType' => $translate->_('CPU type'),
            'CpuClock' => $translate->_('CPU clock (MHz)'),
            'CpuCores' => $translate->_('CPU cores'),
            'PhysicalMemory' => $translate->_('Physical memory'),
            'SwapMemory' => $translate->_('Swap memory'),
            'Manufacturer' => $translate->_('Manufacturer'),
            'Model' => $translate->_('Model'),
            'Serial' => $translate->_('Serial number'),
            'AssetTag' => $translate->_('Asset tag'),
            'BiosVersion' => $translate->_('BIOS version'),
            'BiosDate' => $translate->_('BIOS date'),
            'Volume.Size' => $translate->_('Volume size (MB)'),
            'Volume.FreeSpace' => $translate->_('Volume free space (MB)'),
            'DnsServer' => $translate->_('DNS server'),
            'DefaultGateway' => $translate->_('Default gateway'),
            'NetworkInterface.IpAddress' => $translate->_('IP address'),
            'NetworkInterface.MacAddress' => $translate->_('MAC address'),
            'NetworkInterface.Subnet' => $translate->_('Network address'),
            'NetworkInterface.Netmask' => $translate->_('Network Mask'),
            'Printer.Name' => $translate->_('Printer name'),
            'Printer.Port' => $translate->_('Printer port'),
            'Printer.Driver' => $translate->_('Printer driver'),
            'OcsAgent' => $translate->_('OCS agent'),
            'Display.Manufacturer' => $translate->_('Monitor: manufacturer'),
            'Display.Description' => $translate->_('Monitor: description'),
            'Display.Serial' => $translate->_('Monitor: serial'),
            'Display.ProductionDate' => $translate->_('Monitor: production date'),
            'DisplayController.Name' => $translate->_('Display controller'),
            'DisplayController.Memory' => $translate->_('GPU memory'),
            'Modem.Name' => $translate->_('Modem'),
            'AudioDevice.Name' => $translate->_('Audio device'),
            'Controller.Name' => $translate->_('Controller'),
            'Port.Name' => $translate->_('Port name'),
            'ExtensionSlot.Name' => $translate->_('Extension slot'),
        );

        if (!Model_Database::supportsMsOfficeKeyPlugin()) {
            unset($this->_filters['MsOfficeProduct.ProductKey']);
            unset($this->_filters['MsOfficeProduct.ProductId']);
        }

        // Append filters and labels for registry values/data
        $regValues = Model_RegistryValue::createStatementStatic();
        while ($regValue = $regValues->fetchObject('Model_RegistryValue')) {
            $name = $regValue->getName();
            $this->_filters["Registry.$name"] = "Registry: $name";
        }

        // Append filters and labels for user defined info
        $template = $translate->_('User defined: %s');
        $types = Model_UserDefinedInfo::getTypes();
        foreach ($types as $name => $type) {
            $key = "UserDefinedInfo.$name";
            switch ($type) {
                case 'text':
                case 'clob':
                    break;
                case 'integer':
                    $this->_typeInteger[] = $key;
                    break;
                case 'float':
                    $this->_typeFloat[] = $key;
                    break;
                case 'date':
                    $this->_typeDate[] = $key;
                    break;
                default:
                    throw new UnexpectedValueException('Unsupported datatype: ' . $type);
            }
            if ($name == 'TAG') {
                $label = $translate->_('Category');
            } else {
                $label = $name;
            }
            $this->_filters[$key] = sprintf($template, $label);
        }

        $filter = new Zend_Form_Element_Select('filter');
        $filter->setLabel($translate->_('Search for'))
               ->setAttrib('onchange', 'changeFilter();') // Show and hide elements for selected filter
               ->setDisableTranslator(true) // Translate manually to make xgettext find the strings
               ->setMultiOptions($this->_filters);
        $this->addElement($filter);

        // Only displayed for ordinal searches
        $operator = new Zend_Form_Element_Select('operator');
        $operator->setDisableTranslator(true) // don't translate values
                 ->setMultiOptions(
                     array(
                         'eq' => '=',
                         'ne' => '!=',
                         'lt' => '<',
                         'le' => '<=',
                         'ge' => '>=',
                         'gt' => '>',
                     )
                 )
                 ->setLabel($translate->_('Operator')); // translate manually
        $this->addElement($operator);

        $search = new Zend_Form_Element_Text('search');
        $search->setLabel('Value');
        // Extra options like validators are set dynamically upon validation.
        $this->addElement($search);

        // Only displayed for text searches
        $exact = new Zend_Form_Element_Checkbox('exact');
        $exact->setLabel('Exact match');
        $this->addElement($exact);

        $invert = new Zend_Form_Element_Checkbox('invert');
        $invert->setLabel('Invert results');
        $this->addElement($invert);

        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('Search')
               ->setIgnore(true);
        $this->addElement($submit);
    }

    /**
     * Set options on 'search' element depending on filter
     *
     * This MUST be called before calling any validation method to get correct
     * results, except for {@link isValid()} which already calls this.
     * @param string $filter Set appropriate options for this filter.
     */
    public function setSearchOptions($filter)
    {
        $search = $this->getElement('search');
        $search->clearValidators();
        switch ($this->_getTypeFromFilter($filter)) {
            case 'integer':
                $search->setRequired(true);
                $search->addValidator('Digits');
                break;
            case 'float':
                $search->setRequired(true);
                $search->addValidator('Float');
                break;
            case 'date':
                $search->setRequired(true);
                $search->addValidator(new Braintacle_Validate_Date);
                break;
            default:
                // arbitrary string, just check length
                $search->setRequired(false);
                $search->addValidator('StringLength', false, array(0, 255));
        }
    }

    /**
     * Validate the form
     * @param array $data
     * @return boolean
     */
    public function isValid($data)
    {
        // Set options of the 'search' element depending on the filter before
        // validation, because integers, dates etc. require different processing.
        if (!isset($data['filter'])) {
            throw new InvalidArgumentException('No filter submitted');
        }
        $this->setSearchOptions($data['filter']);
        return parent::isValid($data);
    }

    /** {@inheritdoc} */
    public function setDefaults(array $defaults)
    {
        if (isset($defaults['filter'])) {
            // Set filter explicitly because it must be initialized to make type detection work
            $this->setDefault('filter', $defaults['filter']);
        }
        return parent::setDefaults($defaults);
    }

    /**
     * Generate a list of properties suitable for the changeFilter() JS function
     *
     * This is a workaround for JavaScript's lack of an array_search()
     * equivalent. The array is converted in to a string with property names
     * separated and encapsulated by commas. The JavaScript code created by
     * {@link render()} contains a function changeFilter() which searches this
     * string for a given property name.
     * @param array $properties Array of property names
     * @return string String of property names
     */
    protected function _getPropertiesString($properties)
    {
        return ',' . implode(',', $properties) . ',';
    }

    /**
     * Render form
     * @param Zend_View_Interface $view
     * @return string
     */
    public function render(Zend_View_Interface $view=null)
    {
        $view = $this->getView();

        // The JavaScript code needs to know about the datatype of the searched
        // value to show and hide the appropriate form elements.
        // If the datatype is not listed here, 'text' is assumed.

        // Generate JavaScript to make this form fully functional.
        $view->headScript()->captureStart();
        ?>

        // Properties for which to perform non-text search
        var typeInteger = "<?php print $this->_getPropertiesString($this->_typeInteger); ?>";
        var typeFloat = "<?php print $this->_getPropertiesString($this->_typeFloat); ?>";
        var typeDate = "<?php print $this->_getPropertiesString($this->_typeDate); ?>";

        /**
         * Event handler for Filter combobox.
         * Displays or hides elements according to selected filter.
         */
        function changeFilter()
        {
            // The filter is encapsulated in commas to prevent accidental substring matching.
            var filter = "," + document.getElementById("filter").value + ",";

            if (typeInteger.search(filter) != -1) {
                // Integer search
                display("operator", true);
                display("exact", false);
            } else if (typeFloat.search(filter) != -1) {
                // Date search
                display("operator", true);
                display("exact", false);
            } else if (typeDate.search(filter) != -1) {
                // Date search
                display("operator", true);
                display("exact", false);
            } else {
                // Text search
                display("operator", false);
                display("exact", true);
            }
        }

        /**
         * Hide or display a form element.
         * id (string): element name
         * display (bool): true to display, false to hide
         */
        function display(id, display)
        {
            if (display) {
                display = "block";
            } else {
                display = "none";
            }
            document.getElementById(id+"-label").style.display = display;
            document.getElementById(id+"-element").style.display = display;
        }

        /**
         * Called by body.onload().
         * Hides fields according to selected filter.
         */
        function init()
        {
            changeFilter();
        }

        <?php
        $view->headScript()->captureEnd();

        return parent::render($view);
    }

}
