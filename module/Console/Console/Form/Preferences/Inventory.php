<?php
/**
 * Form for display/setting of 'inventory' preferences
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
 */

namespace Console\Form\Preferences;

/**
 * Form for display/setting of 'inventory' preferences
 */
class Inventory extends AbstractForm
{
    /** {@inheritdoc} */
    public function init()
    {
        parent::init();
        $preferences = $this->get('Preferences');
        $template = $this->_("Mark '%s' by default");

        $inspectRegistry = new \Zend\Form\Element\Checkbox('inspectRegistry');
        $inspectRegistry->setLabel('Inspect registry');
        $preferences->add($inspectRegistry);

        $defaultMergeCustomFields = new \Zend\Form\Element\Checkbox('defaultMergeCustomFields');
        $defaultMergeCustomFields->setLabel(
            sprintf($template, $this->_('Merge user supplied information'))
        );
        $preferences->add($defaultMergeCustomFields);

        $defaultMergeGroups = new \Zend\Form\Element\Checkbox('defaultMergeGroups');
        $defaultMergeGroups->setLabel(
            sprintf($template, $this->_('Merge manual group assignments'))
        );
        $preferences->add($defaultMergeGroups);

        $defaultMergePackages = new \Zend\Form\Element\Checkbox('defaultMergePackages');
        $defaultMergePackages->setLabel(
            sprintf($template, $this->_('Merge missing package assignments'))
        );
        $preferences->add($defaultMergePackages);

        $defaultDeleteInterfaces = new \Zend\Form\Element\Checkbox('defaultDeleteInterfaces');
        $defaultDeleteInterfaces->setLabel(
            sprintf($template, $this->_('Delete interfaces from network listing'))
        );
        $preferences->add($defaultDeleteInterfaces);
    }

    /** {@inheritdoc} */
    public function render(\Zend\View\Renderer\PhpRenderer $view)
    {
        $output = parent::render($view);
        $output .= $view->htmlTag(
            'p',
            $view->htmlTag(
                'a',
                '[' . $view->translate('Manage inventoried registry values') . ']',
                array('href' => $view->consoleUrl('preferences', 'registryvalues'))
            ),
            array('class' => 'textcenter')
        );
        return $output;
    }
}
