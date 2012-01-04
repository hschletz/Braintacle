<?php
/**
 * Render translated text for membership type
 *
 * $Id$
 *
 * Copyright (C) 2011,2012 Holger Schletz <holger.schletz@web.de>
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
 * @filesource
 */
/**
 * @package ViewHelpers
 */
class Zend_View_Helper_MembershipType extends Zend_View_Helper_Abstract
{

    /**
     * Render translated text for membership type
     * @param integer One of {@link Model_GroupMembership::TYPE_DYNAMIC} or {@link Model_GroupMembership::TYPE_STATIC}
     * @return string Translation for either 'automatic' or 'manual'
     */
    function MembershipType($type)
    {
        switch ($type) {
            case Model_GroupMembership::TYPE_DYNAMIC:
                $content = $this->view->translate('automatic');
                break;
            case Model_GroupMembership::TYPE_STATIC:
                $content = $this->view->translate('manual');
                break;
            default:
                throw new UnexpectedValueException(
                    "Invalid group membership type: $type"
                );
        }
        return $content;
    }

}
