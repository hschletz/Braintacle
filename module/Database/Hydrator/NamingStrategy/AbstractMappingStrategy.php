<?php
/**
 * Abstract naming strategy using a map
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

namespace Database\Hydrator\NamingStrategy;

/**
 * Abstract naming strategy using a map
 *
 * Subclasses only need to populate $_hydratorMap and $_extractorMap as simple
 * name => result mappings. Nonexistent names are not checked, but will trigger
 * an E_NOTICE.
 */
abstract class AbstractMappingStrategy implements \Zend\Stdlib\Hydrator\NamingStrategy\NamingStrategyInterface
{
    /**
     * Map used by hydrate()
     * @var string[]
     */
    protected $_hydratorMap = array();

    /**
     * Map used by extract()
     * @var string[]
     */
    protected $_extractorMap = array();

    /** {@inheritdoc} */
    public function hydrate($name)
    {
        return $this->_hydratorMap[$name];
    }

    /** {@inheritdoc} */
    public function extract($name)
    {
        return $this->_extractorMap[$name];
    }
}
