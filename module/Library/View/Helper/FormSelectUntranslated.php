<?php

/**
 * Render a Select element with untranslated value options
 *
 * Copyright (C) 2011-2025 Holger Schletz <holger.schletz@web.de>
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
 * Render a Select element with untranslated value options
 *
 * By default, Select elements are rendered with translated label and value
 * options. Value options are often untranslatable. In that case, use this
 * helper to render the value options unmodified. The label is still translated.
 *
 * To render an element via the FormElement helper, set the element's "type"
 * attribute to "select_untranslated" to make it use this helper.
 *
 * @psalm-suppress UnusedClass
 */
class FormSelectUntranslated extends \Laminas\Form\View\Helper\FormSelect
{
    public function renderOptions(array $options, array $selectedOptions = []): string
    {
        $translatorEnabled = $this->isTranslatorEnabled();
        $this->setTranslatorEnabled(false);
        $output = parent::renderOptions($options, $selectedOptions);
        $this->setTranslatorEnabled($translatorEnabled);
        return $output;
    }
}
