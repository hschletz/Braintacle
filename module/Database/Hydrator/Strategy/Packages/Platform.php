<?php
/**
 * Strategy for Platform attribute
 *
 * Copyright (C) 2011-2020 Holger Schletz <holger.schletz@web.de>
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

namespace Database\Hydrator\Strategy\Packages;

/**
 * Strategy for Platform attribute
 *
 * Invalid values yield NULL, generating an E_NOTICE.
 */
class Platform implements \Zend\Hydrator\Strategy\StrategyInterface
{
    /**
     * Map used by hydrate()
     * @var string[]
     */
    protected $_hydratorMap = array(
        'WINDOWS' => 'windows',
        'LINUX' => 'linux',
        'MacOSX' => 'mac',
    );

    /**
     * Map used by extract()
     * @var string[]
     */
    protected $_extractorMap = array(
        'windows' => 'WINDOWS',
        'linux' => 'LINUX',
        'mac' => 'MacOSX',
    );

    /** {@inheritdoc} */
    public function hydrate($value, ?array $data)
    {
        return $this->_hydratorMap[$value];
    }

    /** {@inheritdoc} */
    public function extract($value, ?object $object = null)
    {
        return $this->_extractorMap[$value];
    }
}
