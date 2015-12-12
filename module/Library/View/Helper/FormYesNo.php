<?php
/**
 * Render a Yes/No form with caption
 *
 * Copyright (C) 2011-2015 Holger Schletz <holger.schletz@web.de>
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
 * Render a Yes/No form with caption
 *
 * The output is a simple form with 2 Buttons, labeled Yes/No or their
 * translations. Form action is empty (current action), method is 'POST' and the
 * buttons are named 'yes' and 'no'. The caption is rendered as a paragraph
 * above the form. Optional parameters are included as hidden elements.
 */
class FormYesNo extends \Zend\View\Helper\AbstractHelper
{
    /**
     * Translate view helper
     * @var \Zend\I18n\View\Helper\Translate
     */
    protected $_translate;

    /**
     * HtmlTag view helper
     * @var \Library\View\Helper\HtmlTag
     */
    protected $_htmlTag;

    /**
     * Constructor
     *
     * @param \Zend\I18n\View\Helper\Translate $translate
     * @param \Library\View\Helper\HtmlTag $htmlTag
     */
    public function __construct(
        \Zend\I18n\View\Helper\Translate $translate,
        \Library\View\Helper\HtmlTag $htmlTag
    ) {
        $this->_translate = $translate;
        $this->_htmlTag = $htmlTag;
    }

    /**
     * Render Form
     *
     * @param string $caption Any valid HTML code. Calling code must escape content if necessary.
     * @param array $params Optional name/value pairs that will be included as hidden elements.
     * @return string Form code
     */
    public function __invoke($caption, $params = array())
    {
        $hiddenFields = '';
        foreach ($params as $name => $value) {
            $hiddenFields .= $this->_htmlTag->__invoke(
                'input',
                null,
                array(
                    'type' => 'hidden',
                    'name' => $name,
                    'value' => $value,
                )
            );
        }
        return sprintf(
            "<div class='form_yesno'>\n" .
            "<p>%s</p>\n" .
            "<form action='' method='POST'>\n" .
            "<p>\n%s<input type='submit' name='yes' value='%s'>&nbsp;\n" .
            "<input type='submit' name='no' value='%s'>\n</p>\n" .
            "</form>\n</div>\n",
            $caption,
            $hiddenFields,
            $this->_translate->__invoke('Yes'),
            $this->_translate->__invoke('No')
        );
    }
}
