<?php
/**
 * Subnet manager
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
     * @return \Zend\Db\ResultSet\AbstractResultSet Result set producing \Model\Network\Subnet
     */

    public function getSubnets($order = null, $direction = 'asc')
    {
        $orderBy = '';
        if ($order) {
            $direction = ($direction == 'desc' ? 'DESC' : 'ASC');
            if ($order == 'CidrAddress') {
                $orderBy = "ORDER BY netid $direction, mask $direction";
            } else {
                $order = $this->_subnets->getHydrator()->extractName($order);
                if ($order) {
                    $orderBy = "ORDER BY $order $direction";
                }
            }
        }
        // The first query covers only subnets with at least 1 inventoried
        // interface, but includes subnets without scanned interfaces.
        // The second query covers only subnets with at least 1 scanned
        // interface, but includes subnets without inventoried interfaces.
        // The UNION eliminates possible overlap.
        $query = <<<EOT
            SELECT
                networks.ipsubnet AS netid,
                networks.ipmask AS mask,
                COUNT(DISTINCT networks.hardware_id) AS num_inventoried,
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
                name
            FROM networks
            LEFT JOIN subnet ON networks.ipsubnet=subnet.netid AND networks.ipmask=subnet.mask
            WHERE
                ipsubnet != '0.0.0.0' AND
                NOT(ipsubnet = '127.0.0.0' AND ipmask='255.0.0.0') AND
                ipsubnet != '169.254.0.0' AND
                ipsubnet NOT LIKE 'fe80%' AND
                description NOT LIKE '%PPP%'
            GROUP BY ipsubnet, ipmask, name
            UNION
            SELECT
                netmap.netid,
                netmap.mask,
                (SELECT COUNT(DISTINCT hardware_id) FROM networks WHERE
                    networks.ipsubnet = netmap.netid AND
                    networks.ipmask = netmap.mask AND
                    networks.description NOT LIKE '%PPP%'
                ) AS num_inventoried,
                (SELECT COUNT(mac) FROM netmap netmap3 WHERE
                    netmap3.netid = netmap.netid AND
                    netmap3.mask = netmap.mask AND
                    netmap3.mac NOT IN(SELECT macaddr FROM networks) AND
                    netmap3.mac IN(SELECT macaddr FROM network_devices)
                ) AS num_identified,
                (SELECT COUNT(mac) FROM netmap netmap3 WHERE
                    netmap3.netid = netmap.netid AND
                    netmap3.mask = netmap.mask AND
                    netmap3.mac NOT IN(SELECT macaddr FROM networks) AND
                    netmap3.mac NOT IN(SELECT macaddr FROM network_devices)
                ) AS num_unknown,
                subnet.name
            FROM netmap
            LEFT JOIN subnet ON netmap.netid=subnet.netid AND netmap.mask=subnet.mask
            GROUP BY netmap.netid, netmap.mask, subnet.name
            $orderBy
EOT;

        return $this->_subnets->getAdapter()->query(
            $query,
            \Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE,
            $this->_subnets->getResultSetPrototype()
        );
    }

    /**
     * Create a Subnet object with given properties
     *
     * Use this method to access a subnet with given address and mask. If a name
     * is defined for this subnet, it is set up for the returned object.
     *
     * @param string $address Network address
     * @param string $mask Network mask
     * @return \Model\Network\Subnet
     * @throws \InvalidArgumentException if address or mask is invalid
     **/
    public function getSubnet($address, $mask)
    {
        try {
            $this->_validate($address, $mask);
        } catch (\UnexpectedValueException $e) {
            throw new \InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
        }
        $select = $this->_subnets->getSql()->select();
        $select->columns(array('netid', 'mask', 'name'))
               ->where(array('netid' => $address, 'mask' => $mask));
        $subnet = $this->_subnets->selectWith($select)->current();
        if (!$subnet) {
            // Construct new Subnet object
            $subnet = new \Model\Network\Subnet;
            $subnet['Address'] = $address;
            $subnet['Mask'] = $mask;
            $subnet['Name'] = null;
        }
        return $subnet;
    }

    /**
     * Store subnet properties
     *
     * @param string $address
     * @param string $mask
     * @param string $name
     * @throws \InvalidArgumentException if address or mask is invalid
     */
    public function saveSubnet($address, $mask, $name)
    {
        try {
            $this->_validate($address, $mask);
        } catch (\UnexpectedValueException $e) {
            throw new \InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
        }
        // Convert empty string to NULL for correct sorting order
        if ($name == '') {
            $name = null;
        }
        if (!$this->_subnets->update(
            array('name' => $name),
            array(
                'netid' => $address,
                'mask' => $mask,
            )
        )) {
            $this->_subnets->insert(
                array(
                    'netid' => $address,
                    'mask' => $mask,
                    'name' => $name,
                )
            );
        }
    }

    /**
     * Validate address and mask
     *
     * @param string $address
     * @param string $mask
     * @throws \UnexpectedValueException if $address or $mask are invalid
     */
    protected function _validate($address, $mask)
    {
        $validator = new \Zend\Validator\Ip(array('allowipv6' => false));
        if (!$validator->isValid($address)) {
            $messages = $validator->getMessages();
            throw new \UnexpectedValueException(
                sprintf('Not an IPv4 address: "%s" (%s)', $address, array_shift($messages))
            );
        }
        // Check $mask for valid syntax and consecutive leading 1 bits.
        // The explicit 0 check is required on 32 bit systems.
        $bits = ip2long($mask);
        if ($bits === false or !($bits === 0 or ctype_digit((string) log(($bits ^ 0xffffffff) + 1, 2)))) {
            throw new \UnexpectedValueException(
                sprintf('Not an IPv4 mask: "%s"', $address)
            );
        }
    }
}
