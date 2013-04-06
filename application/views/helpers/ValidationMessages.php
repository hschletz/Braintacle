<?php
/**
 * Render input validation messages
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
 *
 * @package ViewHelpers
 */
/**
 * Render input validation messages
 * @package ViewHelpers
 */
class Zend_View_Helper_ValidationMessages extends Zend_View_Helper_Abstract
{

    /**
     * Render input validation messages (except in production mode)
     * @param Zend_Filter_Input $input object which contains messages
     * @return string HTML code with messages
     */
    function validationMessages ($input)
    {
        if (APPLICATION_ENV != 'production') {
            $output = '';
            foreach ($input->getMessages() as $messages) {
                foreach ($messages as $message) {
                    $output .= $this->view->htmlTag(
                        'p',
                        $this->view->translate('Invalid parameter: ')
                        . $this->view->escape($message),
                        array('class' => 'red')
                    );
                }
            }
            return $output;
        }
    }

}
