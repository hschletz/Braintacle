<?php

/**
 * Cpu item plugin
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

namespace Model\Client\Plugin;

/**
 * Cpu item plugin
 */
class Cpu extends DefaultPlugin
{
    /** {@inheritdoc} */
    public function select()
    {
        // Older agents report 1 row per core with only a few properties, while
        // newer agents report 1 row per physical CPU with additional
        // properties, most notably "NumCores".
        // To provide unified data structures, cores get aggregated for
        // per-core input. Since the input data does not tell anything about
        // physical CPUs, this will also aggregate multiple physical CPUs of
        // the same manufacturer and type.
        // Per-CPU input is left untouched and the distiction between physical
        // CPUs is left intact.

        // Convert result set to array because its iterator implementation does
        // not allow probing the first element via current().
        $result = iterator_to_array(
            $this->_table->getSql()->prepareStatementForSqlObject($this->_select)->execute()
        );
        if (count($result) and $result[0]['cores'] === null) {
            // Per-core input. Aggregate cores in 3-dimensional array.
            $cpu = array();
            foreach ($result as $row) {
                $client = $row['hardware_id'];
                $manufacturer = $row['manufacturer'];
                $type = $row['type'];
                if (isset($cpu[$client][$manufacturer][$type])) {
                    $cpu[$client][$manufacturer][$type]['cores']++;
                } else {
                    $row['cores'] = 1;
                    $cpu[$client][$manufacturer][$type] = $row;
                }
            }
            // flatten array, resulting in per-CPU list
            $result = array_merge(
                ...array_values(
                    array_merge(...$cpu)
                )
            );
        }

        foreach ($result as &$row) {
            unset($row['hardware_id']);
        }
        unset($row);

        $resultSet = clone $this->_table->getResultSetPrototype();
        $resultSet->initialize($result);
        return $resultSet;
    }

    /** {@inheritdoc} */
    public function columns()
    {
        $columns = array_values($this->_table->getHydrator()->getNamingStrategy()->getExtractionMap());
        $columns[] = 'hardware_id';
        $this->_select->columns($columns);
    }
}
