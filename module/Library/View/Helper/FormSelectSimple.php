<?php

/**
 * Render a SelectSimple element
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

namespace Library\View\Helper;

/**
 * Render a SelectSimple element
 *
 * The option values are not translated, just like the "value" attributes of
 * Select elements.
 */
class FormSelectSimple extends \Laminas\Form\View\Helper\FormSelect
{
    public function renderOptions(array $options, array $selectedOptions = []): string
    {
        $escapeHtml    = $this->getEscapeHtmlHelper();
        $optionStrings = array();
        foreach ($options as $option) {
            $this->validTagAttributes = $this->validOptionAttributes;
            if (\Laminas\Stdlib\ArrayUtils::inArray($option, $selectedOptions)) {
                $attributes = ' selected="selected"';
            } else {
                $attributes = '';
            }
            $optionStrings[] = sprintf(
                '<option%s>%s</option>',
                $attributes,
                $escapeHtml($option)
            );
        }
        return "\n" . implode("\n", $optionStrings) . "\n";
    }
}
