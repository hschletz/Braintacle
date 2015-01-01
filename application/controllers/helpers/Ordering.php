<?php
/**
 * Validate and extract parameters "order" and "direction" from request.
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
 * @package ActionHelpers
 */
/**
 * Validate and extract parameters "order" and "direction" from request.
 * @package ActionHelpers
 */
class Zend_Controller_Action_Helper_Ordering
    extends Zend_Controller_Action_Helper_Abstract
{
    /**
     * Validate and extract parameters "order" and "direction" from request.
     * These parameters will be set as members of the view.
     * @param string $defaultOrder default for missing/invalid "order"
     * @param string $defaultDirection default for missing/invalid "direction"
     * @return array ['order' => $order, 'direction => '$direction]
     */
    function direct($defaultOrder, $defaultDirection='asc')
    {
        $view = $this->getActionController()->view;

        // validate input and provide defaults if necessary
        $validators = array(
            'order' => array(
                new Zend_Validate_NotEmpty(
                    Zend_Validate_NotEmpty::STRING + Zend_Validate_NotEmpty::NULL
                ),
                'default' => $defaultOrder,
            ),
            'direction' => array(
                new Zend_Validate_Regex('/^(asc|desc)$/i'),
                'default' => $defaultDirection,
            ),
        );
        $input = new Zend_Filter_Input(
            null,
            $validators,
            $this->getRequest()->getParams()
        );

        $order = $input->getUnescaped('order');
        if (!$order) {
            $order = $defaultOrder;
        }

        $direction = $input->getUnescaped('direction');
        if (!$direction) {
            $direction = $defaultDirection;
        }

        // set view members
        $view->order = $order;
        $view->direction = $direction;

        // print validation messages if necessary
        print $view->validationMessages($input);

        // compose ORDER BY clause
        return array('order' => $order, 'direction' => $direction);
    }

}
