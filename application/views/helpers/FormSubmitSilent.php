<?php
/**
 * Render submit button without name attribute
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
 *
 * @package ViewHelpers
 */
/**
 * Render submit button without name attribute
 *
 * This is similar to Zend_View_Helper_FormSubmit except that no name attribute
 * is generated. This prevents the submit button from showing up in the
 * submitted form data and is useful for GET forms with only 1 submit button
 * which would otherwise show up in the target URL.
 * @package ViewHelpers
 */
class Zend_View_Helper_FormSubmitSilent extends Zend_View_Helper_FormElement
{
    /**
     * Generate submit button without name attribute
     * 
     * @param string|array $name
     * @param mixed $value
     * @param array $attribs
     * @return mixed
     */
    public function formSubmitSilent($name, $value = null, $attribs = null)
    {
        $info = $this->_getInfo($name, $value, $attribs);
        $attribs = $info['attribs'];
        $attribs['type'] = 'submit';
        if ($info['id']) {
            $attribs['id'] = $info['id'];
        }
        $attribs['value'] = $this->view->escape($info['value']);
        if ($info['disable']) {
            $attribs['disabled'] = $info['disabled'];
        }
        return $this->view->htmlTag('input', null, $attribs, true);
    }
}
