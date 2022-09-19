<?php

/**
 * Tests for the Po class
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

namespace Library\Test\I18n\Translator\Loader;

/**
 * Tests for the Po class
 */
class PoTest extends \PHPUnit\Framework\TestCase
{
    public function testLoad()
    {
        $loader = new \Library\I18n\Translator\Loader\Po();
        $textDomain = $loader->load('de', \Library\Module::getPath('data/Test/I18n/Translator/Loader/PoTest.po'));
        $this->assertInstanceOf('Laminas\I18n\Translator\TextDomain', $textDomain);
        $translations = array(
            'single2single' => 'single to single',
            'single2multi' => 'single to multi',
            'multi2single' => 'multi to single',
            'multi2multi' => 'multi to multi',
            "\\\n\"" => "\"\n\\",
        );
        $this->assertEquals($translations, $textDomain->getArrayCopy());
    }
}
