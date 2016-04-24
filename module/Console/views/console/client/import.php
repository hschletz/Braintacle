<?php
/**
 * Display inventory import form
 *
 * Copyright (C) 2011-2016 Holger Schletz <holger.schletz@web.de>
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

if (isset($this->response)) {
    print $this->htmlTag(
        'p',
        sprintf(
            $this->translate(
                'Upload error. Server %1$s responded with error %2$d: %3$s'
            ),
            $this->escapeHtml($this->uri),
            $this->response->getStatusCode(),
            $this->escapeHtml($this->response->getReasonPhrase())
        ),
        array('class' => 'error')
    );
}

print $this->htmlTag(
    'h1',
    $this->translate('Import locally generated inventory data')
);

print $this->form->render($this);
