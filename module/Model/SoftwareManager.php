<?php
/**
 * Manager for installed software (licenses, blacklists)
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

namespace Model;

/**
 * Manager for installed software (licenses, blacklists)
*/
class SoftwareManager
{
    /**
     * WindowsInstallations table
     * @var \Database\Table\WindowsInstallations
     */
    protected $_windowsInstallations;

    /**
     * Constructor
     *
     * @param \Database\Table\WindowsInstallations $windowsInstallations
     */
    public function __construct(\Database\Table\WindowsInstallations $windowsInstallations)
    {
        $this->_windowsInstallations = $windowsInstallations;
    }

    /**
     * Get number of installations with manually entered Windows product key
     *
     * @return integer
     **/
    public function getNumManualProductKeys()
    {
        return $this->_windowsInstallations->getAdapter()->query(
            'SELECT COUNT(manual_product_key) AS num FROM braintacle_windows WHERE manual_product_key IS NOT NULL',
            \Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE
        )->current()['num'];
    }

    /**
     * Override Windows product key
     *
     * @param \Model_Computer $client Client for which product key is set
     * @param string $productKey New product key
     * @throws \InvalidArgumentException if $productKey is syntactically invalid
     */
    public function setProductKey(\Model_Computer $client, $productKey)
    {
        if (empty($productKey) or $productKey == $client['Windows']['ProductKey']) {
            $productKey = null;
        } else {
            $validator = new \Library\Validator\ProductKey;
            if (!$validator->isValid($productKey)) {
                throw new \InvalidArgumentException(current($validator->getMessages()));
            }
        }

        if (
            !$this->_windowsInstallations->update(
                array('manual_product_key' => $productKey),
                array('hardware_id' => $client['Id'])
            )
        ) {
            $this->_windowsInstallations->insert(
                array(
                    'hardware_id' => $client['Id'],
                    'manual_product_key' => $productKey,
                )
            );
        }
    }
}
