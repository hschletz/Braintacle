<?php

/**
 * HTML page template
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

print $this->doctype();
print "\n<html>\n";
print "<head>\n";

$this->headMeta()->appendHttpEquiv('Content-Type', 'text/html; charset=UTF-8');
print $this->headMeta();
print "\n";
print $this->headTitle();
print "\n";
print $this->HeadLink()->appendStylesheet(
    $this->basePath('style.css') . '?' . filemtime(__DIR__ . '/../../../../public/style.css')
);
print "\n";

print $this->consoleScript();
print "\n";

print "</head>\n";
print "<body>\n";
print "<div id='content'>\n";
print $this->content;
print "\n</div> <!-- #content -->\n";

// Render menu only if a user is logged in.
// Since menus require a matched route, check for routing errors.
if ($this->identity() and !isset($this->noRoute)) {
    print "<div id='menu'>\n";

    $menu = $this->navigation()->menu($this->menu);

    // Top level menu
    print $menu->renderMenu(
        null,
        array(
            'maxDepth' => 0,
            'ulClass' => 'navigation',
        )
    );
    print "\n";

    // Submenu of active branch, if present
    print $menu->renderMenu(
        null,
        array(
            'ulClass' => 'navigation navigation_sub',
            'minDepth' => 1,
            'onlyActiveBranch' => true,
        )
    );

    // Logout button
    print "\n<div id='logout'>\n";
    print $this->htmlElement(
        'a',
        $this->translate('Logout'),
        array('href' => $this->consoleUrl('login', 'logout'))
    );

    print "</div> <!-- #logout -->\n";
    print "</div> <!-- #menu -->\n";
}

print "</body>\n";
print "</html>\n";
