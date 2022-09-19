<?php

/**
 * Form for display/setting of 'inventory' preferences
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

        $inspectRegistry = new \Laminas\Form\Element\Checkbox('inspectRegistry');
        $inspectRegistry->setLabel('Inspect registry');
        $preferences->add($inspectRegistry);

        $defaultMergeCustomFields = new \Laminas\Form\Element\Checkbox('defaultMergeCustomFields');
        $defaultMergeCustomFields->setLabel('Merge user supplied information by default');
        $preferences->add($defaultMergeCustomFields);

        $defaultMergeConfig = new \Laminas\Form\Element\Checkbox('defaultMergeConfig');
        $defaultMergeConfig->setLabel('Merge client configuration by default');
        $preferences->add($defaultMergeConfig);

        $defaultMergeGroups = new \Laminas\Form\Element\Checkbox('defaultMergeGroups');
        $defaultMergeGroups->setLabel('Merge manual group assignments by default');
        $preferences->add($defaultMergeGroups);

        $defaultMergePackages = new \Laminas\Form\Element\Checkbox('defaultMergePackages');
        $defaultMergePackages->setLabel('Merge missing package assignments by default');
        $preferences->add($defaultMergePackages);

        $defaultMergeProductKey = new \Laminas\Form\Element\Checkbox('defaultMergeProductKey');
        $defaultMergeProductKey->setLabel('Keep manually entered Windows product key by default');
        $preferences->add($defaultMergeProductKey);

        $defaultDeleteInterfaces = new \Laminas\Form\Element\Checkbox('defaultDeleteInterfaces');
        $defaultDeleteInterfaces->setLabel('Delete interfaces from network listing by default');
        $preferences->add($defaultDeleteInterfaces);
    }

    /** {@inheritdoc} */
    public function render(\Laminas\View\Renderer\PhpRenderer $view)
    {
        $output = parent::render($view);
        $output .= $view->htmlElement(
            'p',
            $view->htmlElement(
                'a',
                '[' . $view->translate('Manage inventoried registry values') . ']',
                array('href' => $view->consoleUrl('preferences', 'registryvalues'))
            ),
            array('class' => 'textcenter')
        );
        return $output;
    }
}
