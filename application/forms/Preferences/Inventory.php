<?php
/**
 * Form for display/setting of 'inventory' preferences
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
 * @package Forms
 */
/**
 * Form for display/setting of 'inventory' preferences
 * @package Forms
 */
class Form_Preferences_Inventory extends Form_Preferences
{

    /** {@inheritdoc} */
    protected $_types = array(
        'InspectRegistry' => 'bool',
        'DefaultMergeUserdefined' => 'bool',
        'DefaultMergeGroups' => 'bool',
        'DefaultMergePackages' => 'bool',
        'DefaultDeleteInterfaces' => 'bool',
    );

    /**
     * Translate labels before calling parent implementation
     */
    public function init()
    {
        $translate = Zend_Registry::get('Zend_Translate');
        $this->_labels = array(
            'InspectRegistry' => $translate->_(
                'Inspect registry'
            ),
            'DefaultMergeUserdefined' => sprintf(
                $translate->_('Mark \'%s\' by default'),
                $translate->_('Merge user supplied information')
            ),
            'DefaultMergeGroups' => sprintf(
                $translate->_('Mark \'%s\' by default'),
                $translate->_('Merge manual group assignments')
            ),
            'DefaultMergePackages' => sprintf(
                $translate->_('Mark \'%s\' by default'),
                $translate->_('Merge missing package assignments')
            ),
            'DefaultDeleteInterfaces' => sprintf(
                $translate->_('Mark \'%s\' by default'),
                $translate->_('Delete interfaces from network listing')
            ),
        );
        parent::init();

        $inspectRegistry = $this->getElement('InspectRegistry');
        $inspectRegistry->removeDecorator('Label');
        $inspectRegistry->addDecorator(
            'Callback',
            array(
                'callback' => array($this, 'inspectRegistryLabel'),
                'placement' => 'prepend',
            )
        );
    }

    /**
     * @ignore
     * Decorator callback that renders the label for "Inspect Registry" with
     * additional link to the registry value management form
     */
    public function inspectRegistryLabel($content, $element, array $options)
    {
        $view = $element->getView();
        $name = $element->getName();
        $link = $view->htmlTag(
            'a',
            $view->escape(
                '[' . $view->translate('Manage inventoried values') . ']'
            ),
            array(
                'href' => $view->url(
                    array(
                        'controller' => 'preferences',
                        'action' => 'registryvalues',
                    )
                )
            )
        );
        $label = $view->escape($element->getLabel()) . '<br>' . $link;
        return $view->htmlTag(
            'dt',
            $view->formLabel(
                $name,
                $label,
                array(
                    'escape' => false,
                    'class' => 'optional',
                )
            ),
            array('id' => "$name-label")
        );
    }
}
