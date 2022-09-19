<?php

/**
 * Tests for Model\Client\Plugin\Software
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

namespace Model\Test\Client\Plugin;

use Database\AbstractTable;
use Laminas\Db\Sql\Sql;
use Model\Client\Plugin\Software;
use PHPUnit\Framework\MockObject\MockObject;

class SoftwareTest extends \PHPUnit\Framework\TestCase
{
    public function testColumns()
    {
        $select = $this->createMock('Laminas\Db\Sql\Select');
        $select->expects($this->once())->method('columns')->with([
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
            'is_android' => 'isAndroid',
            'display',
        ]);

        $sql = $this->createStub(Sql::class);
        $sql->method('select')->willReturn($select);

        $table = $this->createStub(AbstractTable::class);
        $table->method('getSql')->willReturn($sql);

        /** @var MockObject$Software */
        $model = $this->getMockBuilder(Software::class)
                      ->setConstructorArgs([$table])
                      ->onlyMethods(['getIsAndroidExpression'])
                      ->getMock();
        $model->method('getIsAndroidExpression')->willReturn('isAndroid');

        $model->columns();
    }
}
