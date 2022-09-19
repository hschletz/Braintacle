<?php

/**
 * Software item plugin
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

use Laminas\Db\Sql\Predicate;

/**
 * Software item plugin
 *
 * Provides "Software.NotIgnored" filter to list only software which has not
 * been explicitly marked as ignored. The filter only has to be present; its
 * argument is not evaluated.
 */
class Software extends AddOsColumns
{
    /** {@inheritdoc} */
    public function columns()
    {
        // Hydrator does not provide the names
        $this->_select->columns([
            'name',
            'version',
            'comment',
            'publisher',
            'install_location',
            'is_hotfix',
            'guid',
            'language',
            'installation_date',
            'architecture',
            'size',
            'is_android' => $this->getIsAndroidExpression(),
            'display',
        ]);
    }

    /** {@inheritdoc} */
    public function where($filters)
    {
        parent::where($filters);

        if (is_array($filters) and array_key_exists('Software.NotIgnored', $filters)) {
            $this->_select->where(
                new Predicate\PredicateSet(
                    array(
                        new Predicate\IsNull('display'),
                        new Predicate\Operator('display', '=', true)
                    ),
                    Predicate\PredicateSet::COMBINED_BY_OR
                )
            );
        }
    }
}
