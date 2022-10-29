<?php

/**
 * Display Windows-specific information
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

print $this->clientHeader($this->client);

$windows = $this->windows;

$this->form->prepare();
print $this->plugin('Form')->openTag($this->form);

$format = "<tr>\n<td class='label'>%s</td>\n<td>%s</td>\n</tr>\n";
$key = $this->form->get('Key');
$messages = $key->getMessages();

print "<table class='textnormalsize'>\n";
printf(
    $format,
    $this->translate('Company'),
    $this->escapeHtml($windows['Company'])
);
printf(
    $format,
    $this->translate('Owner'),
    $this->escapeHtml($windows['Owner'])
);
printf(
    $format,
    $this->translate('Product ID'),
    $this->escapeHtml($windows['ProductId'])
);
printf(
    $format,
    $this->translate('Product key (reported by agent)'),
    $this->escapeHtml($windows['ProductKey'])
);
printf(
    $format,
    $this->translate($key->getLabel()),
    $this->formText($key)
);
if ($messages) {
    printf(
        $format,
        '',
        $this->formElementErrors($key, array('class' => 'error'))
    );
}
printf(
    $format,
    $this->formHidden($this->form->get('_csrf')),
    $this->formSubmit($this->form->get('Submit'))
);
print "</table>\n";
print $this->plugin('Form')->closeTag();
