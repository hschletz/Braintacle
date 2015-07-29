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

/**
 * Client manager
 */
class ClientManager
{
    /**
     * Return clients matching criteria
     *
     * @param array $properties Properties to be returned. If empty or null, return all properties.
     * @param string $order Property to sort by
     * @param string $direction One of [asc|desc]
     * @param string|array $filter Name or array of names of a pre-defined filter routine
     * @param string|array $search Search parameter(s) passed to the filter. May be case sensitive depending on DBMS.
     * @param string|array $operator Comparision operator
     * @param bool|array $invert Invert query results (return clients NOT matching criteria)
     * @param bool $addSearchColumns Add columns with search criteria (default).
     *                               Set to false to return only columns specified by $columns.
     * @param bool $distinct Force distinct results.
     * @param bool $query Perform query and return array (default).
     *                    Set to false to return a \Zend_Db_Select object.
     * @return \Model\Client\Client[]|\Zend_Db_Select Query result or Query object
     */
    public function getClients(
        $properties=null,
        $order=null,
        $direction='asc',
        $filter=null,
        $search=null,
        $operator=null,
        $invert=null,
        $addSearchColumns=true,
        $distinct=false,
        $query=true
    )
    {
        $select = \Model_Computer::createStatementStatic(
            $properties,
            $order,
            $direction,
            $filter,
            $search,
            $invert,
            $operator,
            $addSearchColumns,
            false,
            $distinct
        );
        if ($query) {
            $statement = $select->query();
            $result = array();
            while ($row = $statement->fetchObject('Model\Client\Client')) {
                $result[] = $row;
            }
            return $result;
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
        return $result[0];
    }
}
