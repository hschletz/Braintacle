<?php
/**
 * Subnet manager
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

namespace Model\Network;

/**
 * Subnet manager
 */
class SubnetManager
{
    /**
     * Subnets table
     * @var \Database\Table\Subnets
     */
    protected $_subnets;

    /**
     * Constructor
     *
     * @param \Database\Table\Subnets $subnets
     */
    public function __construct(\Database\Table\Subnets $subnets)
    {
        $this->_subnets = $subnets;
    }

    /**
     * Return all subnets, including statistics
     *
     * @param string $order Property to sort by, default: null
     * @param string $direction One of [asc|desc].
     * @return \Zend\Db\ResultSet\AbstractResultSet Result set producing \Model_Subnet
     */
    public function getSubnets($order=null, $direction='asc')
    {
        $subnet = new \Model_Subnet;
        $resultPrototype = new \Zend\Db\ResultSet\HydratingResultSet(null, $subnet);
        $orderBy = \Model_Subnet::getOrder($order, $direction, $subnet->getPropertyMap());
        $query = <<<EOT
            SELECT
                networks.ipsubnet,
                networks.ipmask,
                COUNT(networks.ipmask) AS num_inventoried,
                (SELECT COUNT(mac) FROM netmap WHERE
                    netid = networks.ipsubnet AND
                    mask = networks.ipmask AND
                    mac NOT IN(SELECT macaddr FROM networks) AND
                    mac IN(SELECT macaddr FROM network_devices)
                ) AS num_identified,
                (SELECT COUNT(mac) FROM netmap WHERE
                    netid = networks.ipsubnet AND
                    mask = networks.ipmask AND
                    mac NOT IN(SELECT macaddr FROM networks) AND
                    mac NOT IN(SELECT macaddr FROM network_devices)
                ) AS num_unknown,
                subnet.name
            FROM networks
            LEFT JOIN subnet ON networks.ipsubnet=subnet.netid AND networks.ipmask=subnet.mask
            WHERE
                ipsubnet != '0.0.0.0' AND
                NOT(ipsubnet = '127.0.0.0' AND ipmask='255.0.0.0') AND
                description NOT LIKE '%PPP%'
            GROUP BY ipsubnet, ipmask, name
            ORDER BY $orderBy
EOT;

        return $this->_subnets->getAdapter()->query(
            $query,
            \Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE,
            $resultPrototype
        );
    }

    /**
     * Create a \Model_Subnet object with given properties
     *
     * Use this method to access a subnet with given address and mask. If a name
     * is defined for this subnet, it is set up for the returned object.
     *
     * @param string $address Network address
     * @param string $mask Network mask
     * @return \Model_Subnet
     * @throws \InvalidArgumentException if address or mask is invalid
     **/
    public function getSubnet($address, $mask)
    {
        $subnet = new \Model_Subnet;
        try {
            $subnet['Address'] = $address;
            $subnet['Mask'] = $mask;
        } catch (\InvalidArgumentException $e) {
            throw new \InvalidArgumentException('Invalid address/mask', 0, $e);
        }
        $properties = $this->_subnets->select(array('netid' => $address, 'mask' => $mask))->current();
        if ($properties) {
            $subnet->name = $properties['name'];
        } else {
            $subnet->name = null;
        }
        return $subnet;
    }
}
