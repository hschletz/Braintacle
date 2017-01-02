<?php
/**
 * Tests for Software hydrator
 *
 * Copyright (C) 2011-2017 Holger Schletz <holger.schletz@web.de>
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

namespace Database\Test\Hydrator;

class SoftwareTest extends \PHPUnit_Framework_TestCase
{
    public function testHydrateWindows()
    {
        $hydrator = $this->getMockBuilder('Database\Hydrator\Software')->setMethods(array('hydrateValue'))->getMock();
        $hydrator->method('hydrateValue')->will(
            $this->returnValueMap(
                array(
                    array('Name', '_name', '_Name'),
                    array('InstallLocation', '_folder', '_InstallLocation'),
                    array('IsHotfix', '_source', '_IsHotfix'),
                    array('InstallationDate', '_installdate', '_InstallationDate'),
                    array('Architecture', '_bitswidth', '_Architecture'),
                )
            )
        );
        $agentData = array(
            'is_windows' => true,
            'name' => '_name',
            'version' => '_version',
            'comments' => '_comment',
            'publisher' => '_publisher',
            'folder' => '_folder',
            'source' => '_source',
            'guid' => '_guid',
            'language' => '_language',
            'installdate' => '_installdate',
            'bitswidth' => '_bitswidth',
        );
        $software = array(
            'Name' => '_Name',
            'Version' => '_version',
            'Comment' => '_comment',
            'Publisher' => '_publisher',
            'InstallLocation' => '_InstallLocation',
            'IsHotfix' => '_IsHotfix',
            'Guid' => '_guid',
            'Language' => '_language',
            'InstallationDate' => '_InstallationDate',
            'Architecture' => '_Architecture',
        );
        $object = new \ArrayObject;
        $this->assertSame($object, $hydrator->hydrate($agentData, $object));
        $this->assertEquals($software, $object->getArrayCopy());
    }

    public function testHydrateUnix()
    {
        $hydrator = $this->getMockBuilder('Database\Hydrator\Software')->setMethods(array('hydrateValue'))->getMock();
        $hydrator->expects($this->never())->method('hydrateValue');
        $agentData = array(
            'is_windows' => false,
            'name' => '_name',
            'version' => '_version',
            'comments' => '_comment',
            'publisher' => 'ignored',
            'folder' => 'ignored',
            'source' => 'ignored',
            'guid' => 'ignored',
            'language' => 'ignored',
            'installdate' => 'ignored',
            'bitswidth' => 'ignored',
            'filesize' => '_filesize',
        );
        $software = array(
            'Name' => '_name',
            'Version' => '_version',
            'Comment' => '_comment',
            'Size' => '_filesize',
        );
        $object = new \ArrayObject;
        $this->assertSame($object, $hydrator->hydrate($agentData, $object));
        $this->assertEquals($software, $object->getArrayCopy());
    }

    public function testExtractWindows()
    {
        $hydrator = $this->getMockBuilder('Database\Hydrator\Software')->setMethods(array('extractValue'))->getMock();
        $hydrator->method('extractValue')->will(
            $this->returnValueMap(
                array(
                    array('source', '_IsHotfix', '_source'),
                    array('installdate', '_InstallationDate', '_installdate'),
                )
            )
        );
        $software = array(
            'Name' => '_Name',
            'Version' => '_Version',
            'Comment' => '_Comment',
            'Publisher' => '_Publisher',
            'InstallLocation' => '_InstallLocation',
            'IsHotfix' => '_IsHotfix',
            'Guid' => '_Guid',
            'Language' => '_Language',
            'InstallationDate' => '_InstallationDate',
            'Architecture' => '_Architecture',
        );
        $agentData = array(
            'name' => '_Name',
            'version' => '_Version',
            'comments' => '_Comment',
            'publisher' => '_Publisher',
            'folder' => '_InstallLocation',
            'source' => '_source',
            'guid' => '_Guid',
            'language' => '_Language',
            'installdate' => '_installdate',
            'bitswidth' => '_Architecture',
            'filesize' => null,
        );
        $this->assertEquals($agentData, $hydrator->extract($software));
    }

    public function testExtractUnix()
    {
        $hydrator = $this->getMockBuilder('Database\Hydrator\Software')->setMethods(array('extractValue'))->getMock();
        $hydrator->expects($this->never())->method('extractValue');
        $software = array(
            'Name' => '_Name',
            'Version' => '_Version',
            'Comment' => '_Comment',
            'Size' => '_Size',
        );
        $agentData = array(
            'name' => '_Name',
            'version' => '_Version',
            'comments' => '_Comment',
            'publisher' => null,
            'folder' => null,
            'source' => null,
            'guid' => null,
            'language' => null,
            'installdate' => null,
            'bitswidth' => null,
            'filesize' => '_Size',
        );
        $this->assertEquals($agentData, $hydrator->extract($software));
    }

    public function hydrateNameProvider()
    {
        return array(
            array('name', 'Name'),
            array('version', 'Version'),
            array('comments', 'Comment'),
            array('publisher', 'Publisher'),
            array('folder', 'InstallLocation'),
            array('source', 'IsHotfix'),
            array('guid', 'Guid'),
            array('language', 'Language'),
            array('installdate', 'InstallationDate'),
            array('bitswidth', 'Architecture'),
            array('filesize', 'Size'),
        );
    }

    /**
     * @dataProvider hydrateNameProvider
     */
    public function testHydrateName($extracted, $hydrated)
    {
        $hydrator = new \Database\Hydrator\Software;
        $this->assertEquals($hydrated, $hydrator->hydrateName($extracted));
    }

    public function testHydrateNameInvalid()
    {
        $this->setExpectedException('DomainException', 'Cannot hydrate name: invalid');
        $hydrator = new \Database\Hydrator\Software;
        $hydrator->hydrateName('invalid');
    }

    public function extractNameProvider()
    {
        return array(
            array('Name', 'name'),
            array('Version', 'version'),
            array('Comment', 'comments'),
            array('Publisher', 'publisher'),
            array('InstallLocation', 'folder'),
            array('IsHotfix', 'source'),
            array('Guid', 'guid'),
            array('Language', 'language'),
            array('InstallationDate', 'installdate'),
            array('Architecture', 'bitswidth'),
            array('Size', 'filesize'),
        );
    }

    /**
     * @dataProvider extractNameProvider
     */
    public function testExtractName($hydrated, $extracted)
    {
        $hydrator = new \Database\Hydrator\Software;
        $this->assertEquals($extracted, $hydrator->extractName($hydrated));
    }

    public function testExtractNameInvalid()
    {
        $this->setExpectedException('DomainException', 'Cannot extract name: Invalid');
        $hydrator = new \Database\Hydrator\Software;
        $hydrator->extractName('Invalid');
    }

    public function hydrateValueProvider()
    {
        return array(
            array('Name', "\xC2\x99", "\xE2\x84\xA2"),
            array('InstallLocation', 'N/A', null),
            array('InstallLocation', 'a/b', 'a\b'),
            array('IsHotfix', '0', true),
            array('IsHotfix', '1', false),
            array('InstallationDate', '2014-12-31', new \DateTime('2014-12-31')),
            array('InstallationDate', '', null),
            array('InstallationDate', null, null),
            array('Architecture', '64', '64'),
            array('Architecture', '32', '32'),
            array('Architecture', '0', null),
            array('Architecture', null, null),
            array('other', 'value', 'value'),
        );
    }

    /**
     * @dataProvider hydrateValueProvider
     */
    public function testHydrateValue($name, $extracted, $hydrated)
    {
        $hydrator = new \Database\Hydrator\Software;
        $this->assertEquals($hydrated, $hydrator->hydrateValue($name, $extracted));
    }

    public function extractValueProvider()
    {
        return array(
            array('source', true, '0'),
            array('source', false, '1'),
            array('installdate', new \DateTime('2014-12-31'), '2014-12-31'),
            array('installdate', '', null),
            array('installdate', null, null),
            array('other', 'value', 'value'),
        );
    }

    /**
     * @dataProvider extractValueProvider
     */
    public function testExtractValue($name, $hydrated, $extracted)
    {
        $hydrator = new \Database\Hydrator\Software;
        $this->assertEquals($extracted, $hydrator->extractValue($name, $hydrated));
    }
}
