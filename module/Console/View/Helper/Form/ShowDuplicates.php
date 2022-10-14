<?php

/**
 * Duplicates form renderer
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

use IntlDateFormatter;
use Laminas\Form\FormInterface;
use Laminas\Form\View\Helper\FormRow;

/**
 * Duplicates form renderer
 */
class ShowDuplicates extends Form
{
    public function render(FormInterface $form): string
    {
        $this->getView()->consoleScript('form_showduplicates.js');

        return parent::render($form);
    }

    public function renderContent(FormInterface $form): string
    {
        $view = $this->getView();

        $consoleUrl = $view->plugin('consoleUrl');
        $dateFormat = $view->plugin('dateFormat');
        $escapeHtml = $view->plugin('escapeHtml');
        $formRow = $view->plugin('formRow');
        $htmlElement = $view->plugin('htmlElement');
        $table = $view->plugin('table');
        $translate = $view->plugin('translate');

        $headers = $table->prepareHeaders(
            [
                'Id' => 'ID',
                'Name' => $translate('Name'),
                'NetworkInterface.MacAddress' => $translate('MAC address'),
                'Serial' => $translate('Serial number'),
                'AssetTag' => $translate('Asset tag'),
                'LastContactDate' => $translate('Last contact'),
            ],
            [
                'order' => $form->getOption('order'),
                'direction' => $form->getOption('direction'),
            ]
        );
        $headers['Id'] = '<input type="checkbox" class="checkAll">' . $headers['Id'];
        $tableContent = $table->row($headers, true);

        foreach ($form->getOption('clients') as $client) {
            $tableContent .= $table->row([
                // ID column: add checkbox. $_POST['clients'] will become an
                // array of selected (possibly duplicate) IDs.
                sprintf(
                    '<input type="checkbox" name="clients[]" value="%d">%d',
                    $client['Id'],
                    $client['Id']
                ),

                // Name column: Hyperlink to "customfields" page of given client
                $htmlElement(
                    'a',
                    $escapeHtml($client['Name']),
                    [
                        'href' => $consoleUrl(
                            'client',
                            'customfields',
                            ['id' => $client['Id']]
                        ),
                    ],
                    true
                ),

                $this->getBlacklistLink('MacAddress', $client['NetworkInterface.MacAddress']),
                $this->getBlacklistLink('Serial', $client['Serial']),
                $this->getBlacklistLink('AssetTag', $client['AssetTag']),

                $escapeHtml(
                    $dateFormat($client['LastContactDate'], IntlDateFormatter::SHORT, IntlDateFormatter::SHORT)
                ),
            ]);
        }

        $formContent = $table->tag($tableContent);

        foreach ($form as $element) {
            $formContent .= $formRow($element, FormRow::LABEL_APPEND);
        }

        return $formContent;
    }

    /**
     * Generate link to blacklist criteria value
     */
    public function getBlacklistLink(string $criteria, ?string $value): string
    {
        if ($value === null) {
            // NULL values are never considered for duplicates and cannot be blacklisted.
            return '';
        }

        $view = $this->getView();
        $consoleUrl = $view->plugin('consoleUrl');
        $escapeHtml = $view->plugin('escapeHtml');
        $htmlElement = $view->plugin('htmlElement');

        return $htmlElement(
            'a',
            $escapeHtml($value),
            [
                'href' => $consoleUrl(
                    'duplicates',
                    'allow',
                    [
                        'criteria' => $criteria,
                        'value' => $value,
                    ]
                ),
            ],
            true
        );
    }
}
