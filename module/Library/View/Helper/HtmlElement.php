<?php
/**
 * Render a single HTML element
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

namespace Library\View\Helper;

/**
 * Render a single HTML element with provided name, content and attributes
 */
class HtmlElement extends \Zend\View\Helper\AbstractHtmlElement
{
    /**
     * List of elements without closing tag for HTML 4
     */
    protected static $_emptyTagsHtml4 = array(
        'area',
        'base',
        'br',
        'col',
        'hr',
        'img',
        'input',
        'link',
        'meta',
        'param',
    );

    /**
     * List of elements without closing tag for HTML 5
     */
    protected static $_emptyTagsHtml5 = array(
        'area',
        'base',
        'br',
        'col',
        'command',
        'hr',
        'img',
        'input',
        'keygen',
        'link',
        'meta',
        'param',
        'source',
    );

    /**
     * Render a single HTML element
     *
     * @param string $element HTML element name
     * @param mixed $content Content to be enclosed within tags, NULL for elements without content
     * @param array $attributes Associative array of attributes for the element. Values are escaped automatically.
     * @param bool $inline Whether the element should appear inline or with newlines
     * @return string
     */
    public function __invoke($element, $content = null, $attributes = null, $inline = false)
    {
        $newline = $inline ? '' : "\n";

        // opening tag
        $output  = "<$element";
        if (is_array($attributes)) {
            $output .= $this->htmlAttribs($attributes);
        }
        if ($content === null) {
            $doctype = $this->getView()->plugin('doctype');
            if ($doctype->isXhtml()) {
                $output .= ' />';
            } else { // HTML
                $emptyTags = $doctype->isHtml5() ? static::$_emptyTagsHtml5 : static::$_emptyTagsHtml4;
                if (in_array(strtolower($element), $emptyTags)) {
                    $output .= '>';
                } else {
                    $output .= "></$element>";
                }
            }
            $output .= $newline;
            return $output;
        }
        $output .= '>';
        $output .= $newline;

        // content
        $output .= $content;
        $output .= $newline;

        // closing tag
        $output .= "</$element>";
        $output .= $newline;

        return $output;
    }
}
