<?php

/**
 * Manager for installed software (licenses, blacklists)
 *
 * Copyright (C) 2011-2022 Holger Schletz <holger.schletz@web.de>
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

use Laminas\Db\ResultSet\ResultSet;

/**
 * Manager for installed software (licenses, blacklists)
*/
class SoftwareManager
{
    /**
     * Software table
     * @var \Database\Table\Software
     */
    protected $_software;

    /**
     * SoftwareDefinitions table
     * @var \Database\Table\SoftwareDefinitions
     */
    protected $_softwareDefinitions;

    /**
     * WindowsInstallations table
     * @var \Database\Table\WindowsInstallations
     */
    protected $_windowsInstallations;

    /**
     * WindowsProductKeys table
     * @var \Database\Table\WindowsProductKeys
     */
    protected $_windowsProductKeys;

    /**
     * Constructor
     *
     * @param \Database\Table\Software $software
     * @param \Database\Table\SoftwareDefinitions $softwareDefinitions
     * @param \Database\Table\WindowsInstallations $windowsInstallations
     * @param \Database\Table\WindowsProductKeys $windowsProductKeys
     */
    public function __construct(
        \Database\Table\Software $software,
        \Database\Table\SoftwareDefinitions $softwareDefinitions,
        \Database\Table\WindowsInstallations $windowsInstallations,
        \Database\Table\WindowsProductKeys $windowsProductKeys
    ) {
        $this->_software = $software;
        $this->_softwareDefinitions = $softwareDefinitions;
        $this->_windowsInstallations = $windowsInstallations;
        $this->_windowsProductKeys = $windowsProductKeys;
    }

    /**
     * Get list of all installed software
     *
     * The optional "Os" filter must have the argument "windows" or "other",
     * limiting results to software installed on the given OS type.
     *
     * The optional "Status" filter knows the following arguments:
     * - "accepted" lists only software explicitly marked for being displayed.
     * - "ignored" lists only software explicitly marked for not being displayed.
     * - "new" lists only software not yet classified.
     * - "all" lists all software (same result as ommitting the filter entirely).
     *
     * Both filters can be combined.
     *
     * The returned names may contain unprintable characters. For display
     * purposes, they should be processed via \Library\Filter\FixEncodingErrors.
     * For internal processing (like passing to setDisplay() or composing search
     * queries), the untouched names should be used.
     *
     * @param array $filters Associative array of filters. Default: none.
     * @param string $order One of "name" or "num_clients", default: "name"
     * @param string $direction Onde of "asc" or "desc", default: "asc"
     * @return ResultSet Result set producing arrays with "name" and "num_clients" keys
     */
    public function getSoftware(array $filters = null, string $order = 'name', string $direction = 'asc'): ResultSet
    {
        $sql = $this->_software->getSql();
        $select = $sql->select();
        $select->columns(
            array(
                'name',
                'num_clients' => new \Laminas\Db\Sql\Literal('COUNT(DISTINCT hardware_id)'),
            )
        );

        if (is_array($filters)) {
            foreach ($filters as $filter => $search) {
                switch ($filter) {
                    case 'Os':
                        $select->join(
                            'hardware',
                            'hardware.id = hardware_id',
                            array(),
                            \Laminas\Db\Sql\Select::JOIN_INNER
                        );
                        switch ($search) {
                            case 'windows':
                                $select->where(new \Laminas\Db\Sql\Predicate\IsNotNull('winprodid'));
                                break;
                            case 'other':
                                $select->where(array('winprodid' => null));
                                break;
                            default:
                                throw new \InvalidArgumentException('Invalid OS filter: ' . $search);
                        }
                        break;
                    case 'Status':
                        if ($search != 'all') {
                            switch ($search) {
                                case 'accepted':
                                    $select->where(array('display' => 1));
                                    break;
                                case 'ignored':
                                    $select->where(array('display' => 0));
                                    break;
                                case 'new':
                                    $select->where(array('display' => null));
                                    break;
                                default:
                                    throw new \InvalidArgumentException('Invalid status filter: ' . $search);
                            }
                        }
                        break;
                    default:
                        throw new \InvalidArgumentException('Invalid filter: ' . $filter);
                }
            }
        }

        $select->group('software_installations.name');

        switch ($order) {
            case 'name':
                $select->order(['software_installations.name' => $direction]);
                break;
            case 'num_clients':
                $select->order(['num_clients' => $direction, 'software_installations.name' => 'asc']);
                break;
            default:
                throw new \InvalidArgumentException('Invalid order column: ' . $order);
                break;
        }

        // Wrap into a ResultSet to support buffering
        $resultSet = new \Laminas\Db\ResultSet\ResultSet(\Laminas\Db\ResultSet\ResultSet::TYPE_ARRAY);
        $resultSet->initialize($sql->prepareStatementForSqlObject($select)->execute());
        return $resultSet;
    }

    /**
     * Accept or ignore a piece of software for display
     *
     * @param string $name
     * @param bool $display
     */
    public function setDisplay($name, $display)
    {
        if (!$this->_softwareDefinitions->update(array('display' => $display), array('name' => $name))) {
            $this->_softwareDefinitions->insert(array('name' => $name, 'display' => $display));
        }
    }

    /**
     * Get number of installations with manually entered Windows product key
     *
     * @return integer
     **/
    public function getNumManualProductKeys()
    {
        $sql = $this->_windowsInstallations->getSql();
        $select = $sql->select();
        $select->columns(array('num' => new \Laminas\Db\Sql\Literal('COUNT(manual_product_key)')))
               ->where(new \Laminas\Db\Sql\Predicate\IsNotNull('manual_product_key'));
        return $sql->getAdapter()->query(
            $sql->buildSqlString($select),
            \Laminas\Db\Adapter\Adapter::QUERY_MODE_EXECUTE
        )->current()['num'];
    }

    /**
     * Override Windows product key
     *
     * @param \Model\Client\Client $client Client for which product key is set
     * @param string $productKey New product key
     * @throws \InvalidArgumentException if $productKey is syntactically invalid
     */
    public function setProductKey(\Model\Client\Client $client, $productKey)
    {
        if (empty($productKey) or $productKey == $client['Windows']['ProductKey']) {
            $productKey = null;
        } else {
            $validator = new \Library\Validator\ProductKey();
            if (!$validator->isValid($productKey)) {
                throw new \InvalidArgumentException(current($validator->getMessages()));
            }
        }

        if (
            !$this->_windowsProductKeys->update(
                array('manual_product_key' => $productKey),
                array('hardware_id' => $client['Id'])
            )
        ) {
            $this->_windowsProductKeys->insert(
                array(
                    'hardware_id' => $client['Id'],
                    'manual_product_key' => $productKey,
                )
            );
        }
    }
}
