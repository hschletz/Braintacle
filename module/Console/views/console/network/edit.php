<?php

/**
 * Display form for network device identification
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
 *
 */

$device = $this->device;

print $this->htmlElement('h1', $this->translate('Edit network device'));

$this->form->prepare();
print $this->plugin('Form')->openTag($this->form);

$format = "<tr>\n<td class='label'>%s</td>\n<td>%s</td>\n</tr>\n";
print "<table class='textnormalsize'>\n";
printf(
    $format,
    $this->translate('MAC address'),
    $this->escapeHtml($device['MacAddress'])
);
printf(
    $format,
    $this->translate('Vendor'),
    $this->escapeHtml($device['MacAddress']->getVendor())
);
printf(
    $format,
    $this->translate('IP address'),
    $this->escapeHtml($device['IpAddress'])
);
printf(
    $format,
    $this->translate('Hostname'),
    $this->escapeHtml($device['Hostname'])
);
printf(
    $format,
    $this->translate('Date'),
    $this->escapeHtml(
        $this->dateFormat($device['DiscoveryDate'], \IntlDateFormatter::MEDIUM, \IntlDateFormatter::MEDIUM)
    )
);

foreach (array('Type', 'Description') as $name) {
    $element = $this->form->get($name);
    printf(
        $format,
        $this->translate($element->getLabel()),
        $this->formElement($element)
    );
    if ($element->getMessages()) {
        printf(
            $format,
            '',
            $this->formElementErrors($element, array('class' => 'error'))
        );
    }
}

printf(
    $format,
    $this->formHidden($this->form->get('_csrf')),
    $this->formSubmit($this->form->get('Submit'))
);
print "</table>\n";
print $this->plugin('Form')->closeTag();
