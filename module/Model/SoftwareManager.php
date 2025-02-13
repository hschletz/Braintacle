<?php

/**
 * Manager for installed software (licenses, blacklists)
 *
 * Copyright (C) 2011-2025 Holger Schletz <holger.schletz@web.de>
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

use Braintacle\Direction;
use Braintacle\Software\SoftwareFilter;
use Braintacle\Software\SoftwarePageColumn;
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
     * @psalm-suppress PossiblyUnusedMethod (used indirectly via container)
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
     * @return iterable<array{name: string, num_clients: int}>
     */
    public function getSoftware(SoftwareFilter $filter, SoftwarePageColumn $orderBy, Direction $direction): iterable
    {
        $sql = $this->_software->getSql();
        $select = $sql->select();
        $select->columns(
            array(
                'name',
                'num_clients' => new \Laminas\Db\Sql\Literal('COUNT(DISTINCT hardware_id)'),
            )
        );

        if ($filter != SoftwareFilter::All) {
            $select->where(['display' => match ($filter) {
                SoftwareFilter::Accepted => 1,
                SoftwareFilter::Ignored => 0,
                SoftwareFilter::New => null,
            }]);
        }

        $select->group('software_installations.name');

        $select->order(match ($orderBy) {
            SoftwarePageColumn::Name => ['software_installations.name' => $direction->value],
            SoftwarePageColumn::NumClients => [
                'num_clients' => $direction->value,
                'software_installations.name' => 'asc',
            ],
        });

        // Wrap into a ResultSet to support buffering
        $resultSet = new ResultSet(ResultSet::TYPE_ARRAY);
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
