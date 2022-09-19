<?php

/**
 * Render software fieldset
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

namespace Console\View\Helper\Form;

use Laminas\Form\FormInterface;

/**
 * Render software fieldset
 */
class Software extends Form
{
    /**
     * Render all
     *
     * @param FormInterface $form Software form
     * @param array[] $software Software list
     * @param array $sorting Sorting for table helper
     * @param string $filter Current filter (accepted, ignored, new, all)
     */
    public function __invoke(
        FormInterface $form = null,
        array $software = [],
        array $sorting = [],
        string $filter = null
    ): string {
        $this->getView()->consoleScript('form_software.js');

        return $this->renderForm($form, $software, $sorting, $filter);
    }

    /**
     * Render content
     *
     * @param FormInterface $form Software form
     * @param array[] $software Software list
     * @param array $sorting Sorting for table helper
     * @param string $filter Current filter (accepted, ignored, new, all)
     */
    public function renderContent(
        FormInterface $form,
        array $software = [],
        array $sorting = [],
        string $filter = null
    ): string {
        $formContent = $this->getView()->formRow($form->get('_csrf'));
        $formContent .= $this->renderButtons($form, $filter);
        $formContent .= $this->renderSoftwareFieldset($form->get('Software'), $software, $sorting);

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
     * @param \Laminas\Form\Fieldset $fieldset Software fieldset
     * @param array[] $software Software list
     * @param array $sorting Sorting for table helper
     */
    public function renderSoftwareFieldset($fieldset, $software, $sorting)
    {
        $view = $this->getView();

        $consoleUrl = $view->plugin('consoleUrl');
        $formRow = $view->plugin('formRow');
        $htmlElement = $view->plugin('htmlElement');
        $table = $view->plugin('table');
        $translate = $view->plugin('translate');

        // Checkbox labels are software names and must not be translated
        $translatorEnabled = $formRow->isTranslatorEnabled();
        $formRow->setTranslatorEnabled(false);

        $headers = $table->prepareHeaders(
            ['name' => $translate('Name'), 'num_clients' => $translate('Count')],
            $sorting
        );
        $headers['name'] = '<input type="checkbox" class="checkAll">' . $headers['name'];
        $output = $table->row($headers, true);

        foreach ($software as $row) {
            $element = $fieldset->get('_' . base64_encode($row['name']));
            $output .= $table->row(
                [
                    'name' => $formRow($element, \Laminas\Form\View\Helper\FormRow::LABEL_APPEND),
                    'num_clients' => $htmlElement(
                        'a',
                        $row['num_clients'],
                        ['href' => $consoleUrl(
                            'client',
                            'index',
                            [
                                'columns' => 'Name,UserName,LastContactDate,InventoryDate,Software.Version',
                                'jumpto' => 'software',
                                'filter' => 'Software',
                                'search' => $row['name'],
                            ]
                        )],
                        true
                    ),
                ],
                false,
                ['num_clients' => 'textright']
            );
        }

        $formRow->setTranslatorEnabled($translatorEnabled);
        return $table->tag($output);
    }
}
