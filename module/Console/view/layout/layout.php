<?php
/**
 * HTML page template
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
 */

print $this->doctype();
print "<html>\n";
print "<head>\n";

$this->headMeta()->appendHttpEquiv('Content-Type', 'text/html; charset=UTF-8');
print $this->headMeta();
print "\n";
print $this->headTitle();
print "\n";
print $this->HeadLink()->appendStylesheet($this->basePath('style.css'));
print "\n";
print $this->headStyle();
print "\n";
print $this->headScript();

print "</head>\n";
print "<body";
$onLoad = $this->placeholder('BodyOnLoad');
// TODO: Replace all init() functions with direct placeholder access and remove invoking handler
$onLoad->append('if (typeof(init) == "function") init()');
if ($onLoad->count()) {
    $onLoad->setSeparator('; ');
    printf(' onload="%s"', $this->escapeHtmlAttr($onLoad));
}
print ">\n<div id='content'>\n";
print $this->content;
print "</div>\n";

if ($this->identity()) {
    print "<div id='menu'>\n";

    $menu = $this->navigation()->menu('Console\Navigation\MainMenu');

    // Top level menu
    print $menu->renderMenu(
        null,
        array(
            'maxDepth' => 0,
            'ulClass' => 'navigation',
        )
    );

    // TODO: render submenu

    // Logout button
    print "<div id='logout'>\n";
    print $this->htmlTag(
        'a',
        $this->translate('Logout'),
        array('href' => $this->consoleUrl('login', 'logout'))
    );

    print "</div>\n"; // id=logout
    print "</div>\n"; // id=menu
}

print "</body>\n";
print "</html>\n";
