<?php
/**
 * Search form.
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
 * @package Forms
 * @filesource
 */

require_once ('Braintacle/Validate/Date.php');

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
class Form_Search extends Zend_Form
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
     * Properties for which to perform a timestamp search
     * @var array
     */
    protected $_typeDate = array(
        'InventoryDate',
        'LastContactDate',
    );

    /**
     * Build form elements
     */
    public function init()
    {
        $translate = Zend_Registry::get('Zend_Translate');

        $this->_filters = array(
            'Name' => $translate->_('Computer name'),
            'UserName' => $translate->_('User name'),
            'UserDomain' => $translate->_('User domain'),
            'Workgroup' => $translate->_('Workgroup'),
            'OsName' => $translate->_('OS name'),
            'OsVersionNumber' => $translate->_('OS version number'),
            'OsVersionString' => $translate->_('OS version string'),
            'OsComment' => $translate->_('OS comment'),
            'WindowsProductkey' => $translate->_('Product key'),
            'Software.Name' => $translate->_('Software: Name'),
            'Software.Version' => $translate->_('Software: Version'),
            'Software.Publisher' => $translate->_('Software: Publisher'),
            'Software.Comment' => $translate->_('Software: Comment'),
            'Software.InstallLocation' => $translate->_('Software: Install location'),
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

        $this->setMethod('post');

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

        // Only displayed for text searches
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
        if (!isset($this->_filters[$filter])) {
            throw new UnexpectedValueException('Invalid filter: ' . $filter);
        }

        $search = $this->getElement('search');
        if (!$search) {
            throw new RuntimeException('Element \'search\' not found!');
        }

        $search->clearValidators();
        if (in_array($filter, $this->_typeInteger)) {
            // expecting an integer
                $search->setRequired(true);
                $search->addValidator('Digits');
        } elseif (in_array($filter, $this->_typeDate)) {
            // expecting a date
                $search->setRequired(true);
                $search->addValidator(new Braintacle_Validate_Date);
        } else {
            // arbitrary string, just check length
                $search->setRequired(false);
                $search->addValidator('StringLength', false, array(0, 255));
        }
    }

    /**
     * Validate the form
     *
     * This implementation sets options of the 'search' element depending on the
     * filter before validation, because integers, dates etc. require different
     * processing.
     */
    public function isValid($data)
    {
        if (isset($data['filter'])) {
            $this->setSearchOptions($data['filter']);
        } else {
            throw new InvalidArgumentException('No filter submitted');
        }
        return parent::isValid($data);
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
     * Generate JavaScript before rendering elements
     */
    public function render(Zend_View_Interface $view=null)
    {
        $view = $this->getView();

        // The JavaScript code needs to know about the datatype of the searched
        // value to show and hide the appropriate form elements.
        // If the datatype is not listed here, 'text' is assumed.

        // Don't let missing translations screw up the JS code
        $displayErrors = ini_get('display_errors');
        ini_set('display_errors', false);

        // Generate JavaScript to make this form fully functional.
        $view->headScript()->captureStart();
        ?>

        // Properties for which to perform non-text search
        var typeInteger = "<?php print $this->_getPropertiesString($this->_typeInteger); ?>";
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
                display("invert", false);
            } else if (typeDate.search(filter) != -1) {
                // Date search
                display("operator", true);
                display("exact", false);
                display("invert", false);
            } else {
                // Text search
                display("operator", false);
                display("exact", true);
                display("invert", true);
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
        ini_set('display_errors', $displayErrors);

        return parent::render($view);
    }

}
