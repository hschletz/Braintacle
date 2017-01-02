<?php
/**
 * Included by client templates to render headline and client navigation
 *
 * Copyright (C) 2011-2017 Holger Schletz <holger.schletz@web.de>
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

print $this->htmlElement(
    'h1',
    sprintf(
        $this->translate('Details for client \'%s\''),
        $this->escapeHtml($this->client['Name'])
    )
);

$menu = $this->navigation('Console\Navigation\ClientMenu')
             ->menu()
             ->setUlClass('navigation navigation_details');
if (!$this->client['Windows'] instanceof \Model\Client\WindowsInstallation) {
    foreach ($menu->findAllBy('windowsOnly', true) as $page) {
        $menu->removePage($page);
    }
}
print $menu;
print "\n\n";
