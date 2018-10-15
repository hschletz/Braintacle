<?php
/**
 * Render software fieldset
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

namespace Console\View\Helper\Form;

/**
 * Render software fieldset
 */
class Software extends \Zend\View\Helper\AbstractHelper
{
    /**
     * Render all
     *
     * @param \Console\Form\Software $form Software form
     * @param array[] $software Software list
     * @param array $sorting Sorting for table helper
     * @param string $filter Current filter (accepted, ignored, new, all)
     */
    public function __invoke(\Console\Form\Software $form, $software, $sorting, $filter)
    {
        $form->prepare();

        $view = $this->getView();
        $formHelper = $view->plugin('consoleForm');
        $formRowHelper = $view->plugin('formRow');

        $formContent = $formHelper->postMaxSizeExceeded();
        $formContent .= $formHelper->openTag($form);
        $formContent .= $formRowHelper($form->get('_csrf'));
        $formContent .= $this->renderButtons($form, $filter);
        $formContent .= $this->renderSoftwareFieldset($form->get('Software'), $software, $sorting);
        $formContent .= $formHelper->closeTag();
        return $formContent;
    }

    /**
     * Render submit buttons
     *
     * @param \Console\Form\Software $fieldset Software form main fieldset
     * @param string $filter Current filter (accepted, ignored, new, all)
     */
    public function renderButtons(\Console\Form\Software $fieldset, $filter)
    {
        $formRow = $this->getView()->plugin('formRow');

        $output = "<div class='textcenter'>\n";
        if ($filter != 'accepted') {
            $output .= $formRow($fieldset->get('Accept'));
        }
        $output .= "\n"; // Whitespace affects rendering
        if ($filter != 'ignored') {
            $output .= $formRow($fieldset->get('Ignore'));
        }
        $output .= "</div>\n";
        return $output;
    }

    /**
     * Render software fieldset as table with checkboxes and counts as links to affected clients
     *
     * @param \Zend\Form\Fieldset $fieldset Software fieldset
     * @param array[] $software Software list
     * @param array $sorting Sorting for table helper
     */
    public function renderSoftwareFieldset($fieldset, $software, $sorting)
    {
        $view = $this->getView();

        $formRow = $view->plugin('formRow');
        $translate = $view->plugin('translate');
        $table = $view->plugin('table');

        // Checkbox labels are software names and must not be translated
        $translatorEnabled = $formRow->isTranslatorEnabled();
        $formRow->setTranslatorEnabled(false);

        $output = $table(
            $software,
            [
                'name' => $translate('Name'),
                'num_clients' => $translate('Count'),
            ],
            $sorting,
            [
                'name' => function ($view, $software) use ($fieldset, $formRow) {
                    $element = $fieldset->get(base64_encode($software['name']));
                    return $formRow($element, \Zend\Form\View\Helper\FormRow::LABEL_APPEND);
                },
                'num_clients' => function ($view, $software) {
                    $htmlElement = $view->plugin('htmlElement');
                    $consoleUrl = $view->plugin('consoleUrl');

                    return $htmlElement(
                        'a',
                        $software['num_clients'],
                        array(
                            'href' => $consoleUrl(
                                'client',
                                'index',
                                array(
                                    'columns' => 'Name,UserName,LastContactDate,InventoryDate,Software.Version',
                                    'jumpto' => 'software',
                                    'filter' => 'Software',
                                    'search' => $software['name'],
                                )
                            ),
                        ),
                        true
                    );
                }
            ],
            ['num_clients' => 'textright']
        );

        $formRow->setTranslatorEnabled($translatorEnabled);
        return $output;
    }
}
