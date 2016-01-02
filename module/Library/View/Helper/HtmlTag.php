<?php
/**
 * Render a single HTML element
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

namespace Library\View\Helper;

/**
 * Render a single HTML element with provided name, content and attributes
 */
class HtmlTag extends \Zend\View\Helper\AbstractHelper
{
    /**
     * EscapeHtmlAttr view helper
     * @var \Zend\View\Helper\EscapeHtmlAttr
     */
    protected $_escapeHtmlAttr;

    /**
     * List of elements without closing tag, depending on doctype
     */
    protected $_emptyTags;

    /**
     * Constructor
     *
     * @param \Zend\View\Helper\EscapeHtmlAttr $escapeHtmlAttr EscapeHtmlAttr helper
     * @param \Zend\View\Helper\Doctype $doctype Doctype helper
     */
    public function __construct(
        \Zend\View\Helper\EscapeHtmlAttr $escapeHtmlAttr,
        \Zend\View\Helper\Doctype $doctype
    ) {
        $this->_escapeHtmlAttr = $escapeHtmlAttr;
        if (!$doctype->isXhtml()) {
            $this->_emptyTags = array('area', 'base', 'br', 'col', 'hr', 'img', 'input', 'link', 'meta', 'param');
            if ($doctype->isHtml5()) {
                $this->_emptyTags = array_merge($this->_emptyTags, array('command', 'keygen', 'source'));
            }
        }
    }

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
            foreach ($attributes as $attribute => $value) {
                $output .= " $attribute=\"" . $this->_escapeHtmlAttr->__invoke($value) . '"';
            }
        }
        if ($content === null) {
            if ($this->_emptyTags) { // HTML
                if (in_array(strtolower($element), $this->_emptyTags)) {
                    $output .= '>';
                } else {
                    $output .= "></$element>";
                }
            } else { // XHTML
                $output .= ' />';
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
