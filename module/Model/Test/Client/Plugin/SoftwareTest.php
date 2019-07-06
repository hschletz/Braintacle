<?php
/**
 * Tests for Model\Client\Plugin\Software
 *
 * Copyright (C) 2011-2019 Holger Schletz <holger.schletz@web.de>
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

class SoftwareTest extends \PHPUnit\Framework\TestCase
{
    public function testColumns()
    {
        $select = $this->createMock('Zend\Db\Sql\Select');
        $select->expects($this->once())->method('columns')->with([
            'name',
            'version',
            'comments',
            'publisher',
            'folder',
            'source',
            'guid',
            'language',
            'installdate',
            'bitswidth',
            'filesize',
            'is_android' => 'isAndroid',
        ]);

        $model = $this->getMockBuilder('Model\Client\Plugin\Software')
                      ->disableOriginalConstructor()
                      ->setMethods(['_getIsAndroidExpression'])
                      ->getMock();
        $model->method('_getIsAndroidExpression')->willReturn('isAndroid');

        $proxy = new \SebastianBergmann\PeekAndPoke\Proxy($model);
        $proxy->_select = $select;

        $model->columns();
    }
}
