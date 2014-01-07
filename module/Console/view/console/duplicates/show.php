<?php
/**
 * Display form for merging duplicate computers
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
 */

// Column headers
$headers = array(
    'Id' => 'ID', // will become column of checkboxes without header
    'Name' => $this->translate('Name'),
    'NetworkInterface.MacAddress' => $this->translate('MAC address'),
    'Serial' => $this->translate('Serial number'),
    'AssetTag' => $this->translate('Asset tag'),
    'LastContactDate' => $this->translate('Last contact'),
);


$renderCriteria = function($view, $computer, $property) {
    $value = $computer[$property];
    if ($property == 'NetworkInterface.MacAddress') {
        $property = 'MacAddress';
    }
    // Hyperlink to blacklist form
    return $view->htmlTag(
        'a',
        $view->escapeHtml($value),
        array(
            'href' => $view->consoleUrl(
                'duplicates',
                'allow',
                array(
                    'criteria' => $property,
                    'value' => $value,
                )
            ),
        ),
        true
    );
};
$renderCallbacks = array(
    'Id' => function($view, $computer) {
        // Display ID and a checkbox.
        // The computers[] name will result in a convenient array of IDs in $_POST.
        $id = $computer['Id'];
        return sprintf(
            '<input type="checkbox" name="computers[]" value="%d">%d',
            $id,
            $id
        );
    },
    'Name' => function($view, $computer) {
        // Hyperlink to "userdefined" page of given computer.
        // This allows for easy review of the information about to be merged.
        return $view->htmlTag(
            'a',
            $view->escapeHtml($computer['Name']),
            array(
                'href' => $view->consoleUrl(
                    'computer',
                    'userdefined',
                    array('id' => $computer['Id'])
                ),
            ),
            true
        );
    },
    'NetworkInterface.MacAddress' => $renderCriteria,
    'Serial' => $renderCriteria,
    'AssetTag' => $renderCriteria,
);

$formContent = $this->table(
    $this->computers,
    $headers,
    $this->vars(),
    $renderCallbacks
);
$formContent .= $this->htmlTag(
    'p',
    sprintf(
        '<input type="checkbox" name="mergeUserdefined" value="1"%s>%s',
        $this->config->defaultMergeUserdefined ? ' checked="checked"' : '',
        $this->translate('Merge user supplied information')
    ),
    array('class' => 'textcenter')
);
$formContent .= $this->htmlTag(
    'p',
    sprintf(
        '<input type="checkbox" name="mergeGroups" value="1"%s>%s',
        $this->config->defaultMergeGroups ? ' checked="checked"' : '',
        $this->translate('Merge manual group assignments')
    ),
    array('class' => 'textcenter')
);
$formContent .= $this->htmlTag(
    'p',
    sprintf(
        '<input type="checkbox" name="mergePackages" value="1"%s>%s',
        $this->config->defaultMergePackages ? ' checked="checked"' : '',
        $this->translate('Merge missing package assignments')
    ),
    array('class' => 'textcenter')
);
$formContent .= sprintf(
    '<p class="textcenter"><input type="submit" value="%s"></p>',
    $this->translate('Merge selected computers')
);

// Display table as part of a form. The form is composed manually as Zend_Form
// is not really suitable for this kind of dynamically generated forms.
print $this->htmlTag(
    'form',
    $formContent,
    array(
        'enctype' => 'multipart/form-data',
        'method' => 'post',
        'action' => $this->consoleUrl('duplicates', 'merge')
    )
);
