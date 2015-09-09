<?php
/**
 * Client manager
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

namespace Model\Client;

use Zend\Db\Sql\Select;
use Zend\Db\Sql\Predicate;

/**
 * Client manager
 */
class ClientManager implements \Zend\ServiceManager\ServiceLocatorAwareInterface
{
    use \Zend\ServiceManager\ServiceLocatorAwareTrait;

    /**
     * Return clients matching criteria
     *
     * @param array $properties Properties to be returned. If empty or null, return all properties.
     * @param string $order Property to sort by
     * @param string $direction One of [asc|desc]
     * @param string|array $filter Name or array of names of a pre-defined filter routine
     * @param string|array $search Search parameter(s) passed to the filter. May be case sensitive depending on DBMS.
     * @param string|array $operators Comparison operator(s)
     * @param bool|array $invert Invert query results (return clients not matching criteria)
     * @param bool $addSearchColumns Add columns with search criteria (default), otherwise only columns from $properties
     * @param bool $distinct Force distinct results.
     * @param bool $query Perform query and return result set (default), return \Zend\Db\Sql\Select object otherwise.
     * @return \Zend\Db\ResultSet\AbstractResultSet|\Zend\Db\Sql\Select Query result or Query object
     * @throws \InvalidArgumentException if a filter or order column is invalid
     * @throws \LogicException if $invertResult is not supported by a filter or type of custom field is not supported
     */
    public function getClients(
        $properties=null,
        $order=null,
        $direction='asc',
        $filter=null,
        $search=null,
        $operators=null,
        $invert=null,
        $addSearchColumns=true,
        $distinct=false,
        $query=true
    )
    {
        $clients = $this->serviceLocator->get('Database\Table\Clients');
        $map = $clients->getHydrator()->getExtractorMap();

        $fromClients = array();
        if (empty($properties)) {
            $properties = array_keys($map); // Select all properties
        }
        foreach ($properties as $property) {
            if (isset($map[$property])) {
                $fromClients[] = $map[$property];
            } elseif (preg_match('/^Windows\.(.*)/', $property, $matches)) {
                $column = $this->serviceLocator->get('Database\Table\WindowsInstallations')
                                               ->getHydrator()
                                               ->extractName($matches[1]);
                $fromWindows["windows_$column"] = $column;
            }
            // Ignore other properties. They might get added by a filter.
        }
        // add PK if not already selected
        if (!in_array('id', $fromClients)) {
            $fromClients[] = 'id';
        }

        // Set up Select object manually instead of pulling it from a table
        // gateway because the base table might be changed later.
        $sql = new \Zend\Db\Sql\Sql($this->serviceLocator->get('Db'));
        $select = $sql->select();
        $select->from('clients');
        $select->columns($fromClients);
        if ($distinct) {
            $select->quantifier('DISTINCT');
        }

        if (isset($fromWindows)) {
            // Use left join because there might be no matching row in the 'windows_installations' table.
            $select->join(
                'windows_installations',
                'windows_installations.client_id = clients.id',
                $fromWindows,
                Select::JOIN_LEFT
            );
        }

        // apply filters
        if (!is_array($filter)) {
            // convert to array if necessary
            $filter = array($filter);
            $search = array($search);
            $operators = array($operators);
            $invert = array($invert);
        }
        foreach ($filter as $index => $type) {
            $arg = $search[$index];
            $operator = $operators[$index];
            $matchExact = ($operator == 'eq');
            $invertResult = $invert[$index];
            switch ($type) {
                case '':
                    break; // No filter requested
                case 'Id':
                    if ($invertResult) {
                        throw new \LogicException("invertResult cannot be used on Id filter");
                    }
                    $select->where(array('clients.id' => $arg));
                    break;
                case 'AssetTag':
                case 'BiosDate':
                case 'BiosVersion':
                case 'CpuType':
                case 'DnsServer':
                case 'DefaultGateway':
                case 'Manufacturer':
                case 'Model':
                case 'Name':
                case 'OcsAgent':
                case 'OsName':
                case 'OsVersionNumber':
                case 'OsVersionString':
                case 'OsComment':
                case 'Serial':
                case 'UserName':
                    $select = $this->_filterByString(
                        $select,
                        'Client',
                        $type,
                        $arg,
                        $matchExact,
                        $invertResult,
                        $addSearchColumns
                    );
                    break;
                case 'CpuClock':
                case 'CpuCores':
                case 'PhysicalMemory':
                case 'SwapMemory':
                    $select = $this->_filterByOrdinal(
                        $select,
                        'Client',
                        $type,
                        $arg,
                        $operator,
                        $invertResult,
                        $addSearchColumns
                    );
                    break;
                case 'InventoryDate':
                case 'LastContactDate':
                    $select = $this->_filterByDate(
                        $select,
                        'Client',
                        $type,
                        $arg,
                        $operator,
                        $invertResult,
                        $addSearchColumns
                    );
                    break;
                case 'PackageNonnotified':
                case 'PackageNotified':
                case 'PackageSuccess':
                case 'PackageError':
                    if ($invertResult) {
                        throw new \LogicException("invertResult cannot be used on $type filter");
                    }
                    $select = $this->_filterByPackage($select, $type, $arg, $addSearchColumns);
                    break;
                case 'Software':
                    if ($invertResult) {
                        throw new \LogicException("invertResult cannot be used on Software filter");
                    }
                    $select
                        ->quantifier('DISTINCT')
                        ->join(
                            'softwares',
                            'softwares.hardware_id = clients.id',
                            $addSearchColumns ? array('software_version' => 'version') : array()
                        )->where(array('softwares.name' => $arg));
                    break;
                case 'MemberOf':
                    if ($invertResult) {
                        throw new \LogicException("invertResult cannot be used on MemberOf filter");
                    }
                    // $arg is expected to be a \Model\Group\Group object.
                    $arg->update();
                    $select
                        ->join(
                            'groups_cache',
                            'groups_cache.hardware_id = clients.id',
                            $addSearchColumns ? array('static') : array()
                        )
                        ->where(
                            array(
                                'groups_cache.group_id' => $arg['Id'],
                                new Predicate\In(
                                    'groups_cache.static',
                                    array(
                                        \Model_GroupMembership::TYPE_DYNAMIC,
                                        \Model_GroupMembership::TYPE_STATIC
                                    )
                                )
                            )
                        );
                    break;
                case 'ExcludedFrom':
                    if ($invertResult) {
                        throw new \LogicException("invertResult cannot be used on ExcludedFrom filter");
                    }
                    // $arg is expected to be a \Model\Group\Group object.
                    $arg->update();
                    $select->join('groups_cache', 'groups_cache.hardware_id = clients.id', array())
                           ->where(
                               array(
                                   'groups_cache.group_id' => $arg['Id'],
                                   'groups_cache.static' => \Model_GroupMembership::TYPE_EXCLUDED
                               )
                           );
                    break;
                case 'Filesystem.Size':
                case 'Filesystem.FreeSpace':
                    // Generic integer filter
                    list($model, $property) = explode('.', $type);
                    $select = $this->_filterByOrdinal(
                        $select,
                        $model,
                        $property,
                        $arg,
                        $operator,
                        $invertResult,
                        $addSearchColumns
                    );
                    break;
                default:
                    if (preg_match('#^CustomFields\\.(.*)#', $type, $matches)) {
                        $property = $matches[1];
                        $fieldType = $this->serviceLocator->get('Model\Client\CustomFieldManager')
                                                          ->getFields()[$property];
                        switch ($fieldType) {
                            case 'text':
                            case 'clob':
                                $select = $this->_filterByString(
                                    $select,
                                    'CustomFields',
                                    $property,
                                    $arg,
                                    $matchExact,
                                    $invertResult,
                                    $addSearchColumns
                                );
                                break;
                            case 'integer':
                            case 'float':
                                $select = $this->_filterByOrdinal(
                                    $select,
                                    'CustomFields',
                                    $property,
                                    $arg,
                                    $operator,
                                    $invertResult,
                                    $addSearchColumns
                                );
                                break;
                            case 'date':
                                $select = $this->_filterByDate(
                                    $select,
                                    'CustomFields',
                                    $property,
                                    $arg,
                                    $operator,
                                    $invertResult,
                                    $addSearchColumns
                                );
                                break;
                            default:
                                throw new \LogicException('Unsupported type: ' . $fieldType);
                        }
                    } elseif (preg_match('/^Registry\\.(.+)/', $type, $matches)) {
                        $property = $matches[1];
                        $select = $this->_filterByString(
                            $select,
                            'Registry',
                            $property,
                            $arg,
                            $matchExact,
                            $invertResult,
                            $addSearchColumns
                        );
                    } elseif (preg_match('/^([a-zA-Z]+)\.([a-zA-Z]+)$/', $type, $matches)) {
                        // apply a generic string filter.
                        $select = $this->_filterByString(
                            $select,
                            $matches[1],
                            $matches[2],
                            $arg,
                            $matchExact,
                            $invertResult,
                            $addSearchColumns
                        );
                    } else {
                        // Filter must be of the form 'Model.Property'.
                        throw new \InvalidArgumentException('Invalid filter: ' . $type);
                    }
            }
        }

        if ($order) {
            if (isset($map[$order])) {
                $order = "clients.$map[$order]";
            } elseif ($order == 'Membership') {
                $order = 'groups_cache.static';
            } elseif (preg_match('/^CustomFields\\.(.+)/', $order, $matches)) {
                $order = 'customfields_' . $this->serviceLocator->get('Model\Client\CustomFieldManager')
                                                                ->getColumnMap()[$matches[1]];
            } elseif (preg_match('/^Windows\.(.+)/', $order, $matches)) {
                $hydrator = $this->serviceLocator->get('Database\Table\WindowsInstallations')->getHydrator();
                $order = 'windows_' . $hydrator->extractName($matches[1]);
            } elseif (preg_match('/^Registry\./', $order)) {
                $order = 'registry_content';
            } elseif (preg_match('/^([a-zA-Z]+)\.([a-zA-Z]+)$/', $order, $matches)) {
                $model = $matches[1];
                $property = $matches[2];
                // Assume column alias 'model_column'
                $tableGateway = $this->serviceLocator->get('Model\Client\ItemManager')->getTable($model);
                $column = $tableGateway->getHydrator()->extractName($property);
                $order = strtolower("{$model}_$column");
            } else {
                throw new \InvalidArgumentException('Invalid order: ' . $order);
            }
            $select->order(array($order => $direction));
        }

        /*
         * Try to optimize the query by removing unnecessary JOINs. The query
         * can be rewritten if all of the following conditions are met:
         * - There is exactly 1 joined table.
         * - The tables are joined by an inner join.
         * - The only column from the 'clients' table is 'id'.
         * In that case, clients.id can be replaced by the 'hardware_id' or
         * 'client_id' column from the joined table.
        */
        $joinedTables = $select->getRawState(Select::JOINS);
        $clientColumns = $select->getRawState(Select::COLUMNS);
        if (count($joinedTables) == 1 and $clientColumns == array('id')) {
            $joinedTable = $joinedTables[0];
            if ($joinedTable['type'] == Select::JOIN_INNER) {
                $joinColumn = (strpos($joinedTable['on'], 'client_id') === false) ? 'hardware_id' : 'client_id';

                // Add column with "id" alias
                $columns = $joinedTable['columns'];
                $columns['id'] = $joinColumn;

                // Replace columns and table
                $select->from($joinedTable['name']);
                $select->columns($columns);
                $select->reset(Select::JOINS);

                // Replace possible clients.id in ORDER BY clause
                $orderSpec = array();
                foreach ($select->getRawState(Select::ORDER) as $column => $direction) {
                    if ($column == 'clients.id') {
                        $column = $joinColumn;
                    }
                    $orderSpec[$column] = $direction;
                }
                $select->reset(Select::ORDER);
                $select->order($orderSpec);
            }
        }

        if ($query) {
            $resultSet = clone $clients->getResultSetPrototype();
            $resultSet->initialize($sql->prepareStatementForSqlObject($select)->execute());
            return $resultSet;
        } else {
            return $select;
        }
    }

    /**
     * Get client with given ID
     *
     * @param integer $id Primary key
     * @return \Model\Client\Client
     * @throws \RuntimeException if there is no client with the given ID
     */
    public function getClient($id)
    {
        $result = $this->getClients(null, null, null, 'Id', $id);
        if (!$result) {
            throw new \RuntimeException("Invalid client ID: $id");
        }
        return $result->current();
    }

    /**
     * Apply a filter for a string value
     *
     * @param \Zend\Db\Sql\Select $select Object to apply the filter to
     * @param string $model Model class (without namespace) containing property
     * @param string $property Property to search in
     * @param string $arg String to search for
     * @param bool $matchExact Disable wildcards ('*', '?', '%', '_') and substring search
     * @param bool $invertResult Return clients not matching criteria
     * @param bool $addSearchColumns Add columns with search criteria to Select object
     * @return Zend\Db\Sql\Select Object with filter applied
     */
    protected function _filterByString(
        $select,
        $model,
        $property,
        $arg,
        $matchExact,
        $invertResult,
        $addSearchColumns
    )
    {
        $arg = (string) $arg; // Treat NULL as empty string
        list($tableGateway, $column) = $this->_filter($select, $model, $property, $addSearchColumns);
        $table = $tableGateway->getTable();

        // Determine comparison operator and prepare search argument
        if ($matchExact) {
            $operator = '=';
        } else {
            // Replace wildcards '*' and '?' with their SQL counterparts '%' and '_'.
            // If $arg contains '%' and '_', they are currently not escaped, i.e. they operate as wildcards too.
            // The result is encapsulated within '%' to support searching for arbitrary substrings.
            $arg = '%' . strtr($arg, '*?', '%_') . '%';
            $operator = $this->serviceLocator->get('Database\Nada')->iLike();
        }
        $where = "$table.$column $operator ?";
        if ($invertResult) {
            // include NULL values
            $where = "($where) IS NOT " . $this->serviceLocator->get('Database\Nada')->booleanLiteral(true);
        }
        $select->where(array($where => $arg));

        return $select;
    }

    /**
     * Apply a filter for an ordinal value (integer, float, ISO 8601 date string)
     *
     * @param \Zend\Db\Sql\Select $select Object to apply the filter to
     * @param string $model Model class (without namespace) containing property
     * @param string $property Property to search in
     * @param string $arg Numeric operand (not validated!)
     * @param string $operator Comparison operator (eq|ne|lt|le|gt|ge)
     * @param bool $invertResult Return clients not matching criteria
     * @param bool $addSearchColumns Add columns with search criteria to Select object
     * @return Zend\Db\Sql\Select Object with filter applied
     * @throws \DomainException if $operator is invalid
     */
    protected function _filterByOrdinal($select, $model, $property, $arg, $operator, $invertResult, $addSearchColumns)
    {
        // Convert abstract operator into SQL operator
        switch ($operator) {
            case 'eq':
                $operator = '=';
                break;
            case 'ne':
                $operator = '!=';
                break;
            case 'lt':
                $operator = '<';
                break;
            case 'le':
                $operator = '<=';
                break;
            case 'gt':
                $operator = '>';
                break;
            case 'ge':
                $operator = '>=';
                break;
            default:
                throw new \DomainException('Invalid comparison operator: ' . $operator);
        }

        list($tableGateway, $column) = $this->_filter($select, $model, $property, $addSearchColumns);

        $where = $tableGateway->getTable() . ".$column $operator ?";
        if ($invertResult) {
            // include NULL values
            $where = "($where) IS NOT " . $this->serviceLocator->get('Database\Nada')->booleanLiteral(true);
        }
        $select->where(array($where => $arg));

        return $select;
    }

    /**
     * Apply a filter for a date value
     *
     * @param \Zend\Db\Sql\Select $select Object to apply the filter to
     * @param string $model Model class (without namespace) containing property
     * @param string $property Property to search in
     * @param mixed $arg Date operand (\DateTime object or 'yyyy-MM-dd' string). Time of day is ignored.
     * @param string $operator Comparison operator (eq|ne|lt|le|gt|ge)
     * @param bool $invertResult Return clients not matching criteria
     * @param bool $addSearchColumns Add columns with search criteria to Select object
     * @return Zend\Db\Sql\Select Object with filter applied
     * @throws \DomainException if $operator is invalid
     */
    protected function _filterByDate($select, $model, $property, $arg, $operator, $invertResult, $addSearchColumns)
    {
        if ($arg instanceof \DateTime) {
            $dayStart = $arg;
        } else {
            $dayStart = \DateTime::createFromFormat('Y-m-d+', $arg);
        }

        if ($model == 'CustomFields') {
            // For plain date columns a simple ordinal comparison is sufficient.
            return $this->_filterByOrdinal(
                $select, $model, $property, $dayStart->format('Y-m-d'), $operator, $invertResult, $addSearchColumns
            );
        }

        // Compare date arguments (ignoring time part) to timestamp columns. The
        // comparison can not be made directly because the date operand would be
        // cast to a timestamp with time part set to midnight. Timestamps with
        // the same day but different time of day would be considered not equal.
        // Instead, values are expected equal if they have the same date,
        // regardless of time. Specifically, the column's value is considered
        // equal to the argument if it is >= 00:00:00 of the argument's day and
        // < 00:00:00 of the next day.
        // Other operations (<, >) are defined accordingly.

        $nada = $this->serviceLocator->get('Database\Nada');

        // Get beginning of day
        $dayStart->modify('midnight');

        // Get beginning of next day
        $dayNext = clone $dayStart;
        $dayNext->modify('next day');

        $dayStart = $dayStart->format($nada->timestampFormatPhp());
        $dayNext = $dayNext->format($nada->timestampFormatPhp());

        list($tableGateway, $column) = $this->_filter($select, $model, $property, $addSearchColumns);
        $table = $tableGateway->getTable();
        $operand = "$table.$column";

        switch ($operator) {
            case 'eq':
                $where["$operand >= ?"] = $dayStart;
                $where["$operand < ?"] = $dayNext;
                break;
            case 'ne':
                $where["$operand < ? OR $operand >= ?"] = array($dayStart, $dayNext);
                break;
            case 'lt':
                $where["$operand < ?"] = $dayStart;
                break;
            case 'le':
                $where["$operand < ?"] = $dayNext;
                break;
            case 'gt':
                $where["$operand >= ?"] = $dayNext;
                break;
            case 'ge':
                $where["$operand >= ?"] = $dayStart;
                break;
            default:
                throw new \DomainException('Invalid comparison operator: ' . $operator);
        }
        if ($invertResult) {
            $conditions = implode(' AND ', array_keys($where));
            if (count($where) == 2) {
                $operands = array_values($where);
            } else {
                $operands = current($where);
            }
            // include NULL values
            $where = array(
                "($conditions) IS NOT " . $this->serviceLocator->get('Database\Nada')->booleanLiteral(true) => $operands
            );
        }
        $select->where($where);

        return $select;
    }

    /**
     * Apply a package filter
     *
     * @param \Zend\Db\Sql\Select $select Object to apply the filter to
     * @param string $filter PackageNonnotified|PackageSuccess|PackageNotified|PackageError
     * @param string $package Package name
     * @param bool $addSearchColumns Add columns with search criteria (Package.Status)
     * @return Zend\Db\Sql\Select Object with filter applied
     */
    protected function _filterByPackage($select, $filter, $package, $addSearchColumns)
    {
        switch ($filter) {
            case 'PackageNonnotified':
                $condition = new Predicate\IsNull('devices.tvalue');
                break;
            case 'PackageSuccess':
                $condition = array('devices.tvalue' => \Model\Package\Assignment::SUCCESS);
                break;
            case 'PackageNotified':
                $condition = array('devices.tvalue' => \Model\Package\Assignment::NOTIFIED);
                break;
            case 'PackageError':
                $condition = new Predicate\Like('devices.tvalue', \Model\Package\Assignment::ERROR_PREFIX . '%');
                break;
        }
        return $select->join(
            'devices',
            'devices.hardware_id = clients.id',
            $addSearchColumns ? array('package_status' => 'tvalue') : array()
        )
        ->join(
            'download_available',
            'download_available.fileid = devices.ivalue',
            array()
        )->where(
            array(
                'download_available.name' => $package,
                'devices.name' => 'DOWNLOAD'
            )
        )->where($condition);
    }

    /**
     * Common operations for string/number/date filter functions
     *
     * This method determines the table and column to search and adds them to
     * $select if necessary.
     *
     * @param \Zend\Db\Sql\Select $select Object to apply the filter to
     * @param string $model Model class (without namespace) containing property
     * @param string $property Property to search in
     * @param bool $addSearchColumns Add columns with search criteria
     * @return array Table gateway and column of search criteria
     */
    protected function _filter($select, $model, $property, $addSearchColumns)
    {
        // Determine table name and column alias
        switch ($model) {
            case 'Client':
                $table = 'Clients';
                $hydrator = $this->serviceLocator->get('Database\Table\Clients')->getHydrator();
                $column = $hydrator->extractName($property);
                $columnAlias = $column;
                break;
            case 'CustomFields':
                $table = 'CustomFields';
                $column = $this->serviceLocator->get('Model\Client\CustomFieldManager')->getColumnMap()[$property];
                $columnAlias = 'customfields_' . $column;
                $fk = 'hardware_id';
                break;
            case 'Registry':
                $table = 'RegistryData';
                $column = 'regvalue';
                $columnAlias = 'registry_content';
                $select->where(array('registry.name' => $property));
                $fk = 'hardware_id';
                break;
            case 'Windows':
                $table = 'WindowsInstallations';
                $hydrator = $this->serviceLocator->get('Database\Table\WindowsInstallations')->getHydrator();
                $column = $hydrator->extractName($property);
                $columnAlias = 'windows_' . $column;
                $fk = 'client_id';
                break;
            default:
                $tableGateway = $this->serviceLocator->get('Model\Client\ItemManager')->getTable($model);
                $column = $tableGateway->getHydrator()->extractName($property);
                $columnAlias = strtolower($model) . '_' . $column;
                $fk = 'hardware_id';
        }

        if (!isset($tableGateway)) {
            $tableGateway = $this->serviceLocator->get("Database\Table\\$table");
        }
        $table = $tableGateway->getTable();

        if ($table == 'clients') {
            if ($addSearchColumns) {
                // Add column if not already present with the same alias
                $columns = $select->getRawState(Select::COLUMNS);
                if (@$columns[$columnAlias] != $column) {
                    $columns[$columnAlias] = $column;
                    $select->columns($columns);
                }
            }
        } else {
            // Join table if not already present
            $rewriteJoins = false;
            $joinedTables = $select->getRawState(Select::JOINS);
            $tablePresent = false;
            foreach ($joinedTables as $joinedTable) {
                if ($joinedTable['name'] == $table) {
                    $tablePresent = true;
                    break;
                }
            }
            if (!$tablePresent) {
                $rewriteJoins = true;
                $joinedTable = array(
                    'name' => $table,
                    'on' => "$table.$fk = clients.id",
                    'columns'=> array(),
                    'type' => Select::JOIN_INNER
                );
            }
            // Add column if not already present with the same alias
            if ($addSearchColumns and @$joinedTable['columns'][$columnAlias] != $column) {
                $rewriteJoins = true;
                $joinedTable['columns'][$columnAlias] = $column;
            }
            // Rewrite joins
            if ($rewriteJoins) {
                $select->reset(Select::JOINS);
                if (!$tablePresent) {
                    $joinedTables[] = $joinedTable;
                }
                foreach ($joinedTables as $table) {
                    if ($table['name'] == $joinedTable['name']) {
                        // Existing spec is out of date for updated tables.
                        // Always replace with new spec.
                        $table = $joinedTable;
                    }
                    $select->join(
                        $table['name'],
                        $table['on'],
                        $table['columns'],
                        $table['type']
                    );
                }
            }
        }

        return array($tableGateway, $column);
    }
}
