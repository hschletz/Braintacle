<?php

/**
 * Item plugin to add "is_windows" and "is_android" boolean columnn
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
 * Item plugin to add "is_windows" and "is_android" boolean columnn
 *
 * The extra columns are required by some hydrators as a hint about the agent
 * type.
 */
class AddOsColumns extends DefaultPlugin
{
    /** {@inheritdoc} */
    public function join()
    {
        $this->_select->join(
            'hardware',
            'hardware.id = hardware_id',
            ['is_windows' => new \Laminas\Db\Sql\Literal('(hardware.winprodid IS NOT NULL)')]
        );
    }

    /**
     * Get SQL expression for the is_android column
     *
     * @return \Laminas\Db\Sql\Literal
     */
    protected function getIsAndroidExpression()
    {
        return new \Laminas\Db\Sql\Literal('EXISTS(SELECT 1 FROM javainfos WHERE javainfos.hardware_id = hardware.id)');
    }
}
