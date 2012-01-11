<?php
/**
 * A dummy translation adapter that returns all strings unchanged
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
 * @package Library
 * @filesource
 */
/**
 * A dummy translation adapter that returns all strings unchanged
 *
 * This is used when the language is 'en', in which case no translation is
 * needed and maintaining empty gettext files would be too much effort.
 * @package Library
 */
class Braintacle_Translate_Adapter_Null extends Zend_Translate_Adapter
{

    /**
     * Generates the adapter
     * @param  array|Zend_Config $options Translation options for this adapter
     * @throws Zend_Translate_Exception
     * @return void
     */
    public function __construct($options = array())
    {
        // Never complain about anything.
        $options['logUntranslated'] = false;
        $options['disableNotices'] = true;
        parent::__construct($options);
    }

    /**
     * Stub for abstract method
     */
    protected function _loadTranslationData($data, $locale, array $options = array())
    {
    }


    /**
     * Implementation for abstract method
     */
    public function toString()
    {
        return 'Null';
    }

}
