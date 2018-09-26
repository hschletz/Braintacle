<?php
/**
 * Tests for Software hydrator
 *
 * Copyright (C) 2011-2018 Holger Schletz <holger.schletz@web.de>
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

namespace Protocol\Test\Hydrator;

class SoftwareTest extends \PHPUnit\Framework\TestCase
{
    public function testHydrateWindows()
    {
        $hydrator = $this->getMockBuilder('Protocol\Hydrator\Software')->setMethods(array('hydrateValue'))->getMock();
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
            'IS_WINDOWS' => true,
            'NAME' => '_name',
            'VERSION' => '_version',
            'COMMENTS' => '_comment',
            'PUBLISHER' => '_publisher',
            'FOLDER' => '_folder',
            'SOURCE' => '_source',
            'GUID' => '_guid',
            'LANGUAGE' => '_language',
            'INSTALLDATE' => '_installdate',
            'BITSWIDTH' => '_bitswidth',
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
        $hydrator = $this->getMockBuilder('Protocol\Hydrator\Software')->setMethods(array('hydrateValue'))->getMock();
        $hydrator->expects($this->never())->method('hydrateValue');
        $agentData = array(
            'IS_WINDOWS' => false,
            'NAME' => '_name',
            'VERSION' => '_version',
            'COMMENTS' => '_comment',
            'PUBLISHER' => 'ignored',
            'FOLDER' => 'ignored',
            'SOURCE' => 'ignored',
            'GUID' => 'ignored',
            'LANGUAGE' => 'ignored',
            'INSTALLDATE' => 'ignored',
            'BITSWIDTH' => 'ignored',
            'FILESIZE' => '_filesize',
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
        $hydrator = $this->getMockBuilder('Protocol\Hydrator\Software')->setMethods(array('extractValue'))->getMock();
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
            'NAME' => '_Name',
            'VERSION' => '_Version',
            'COMMENTS' => '_Comment',
            'PUBLISHER' => '_Publisher',
            'FOLDER' => '_InstallLocation',
            'SOURCE' => '_source',
            'GUID' => '_Guid',
            'LANGUAGE' => '_Language',
            'INSTALLDATE' => '_installdate',
            'BITSWIDTH' => '_Architecture',
            'FILESIZE' => null,
        );
        $this->assertEquals($agentData, $hydrator->extract($software));
    }

    public function testExtractUnix()
    {
        $hydrator = $this->getMockBuilder('Protocol\Hydrator\Software')->setMethods(array('extractValue'))->getMock();
        $hydrator->expects($this->never())->method('extractValue');
        $software = array(
            'Name' => '_Name',
            'Version' => '_Version',
            'Comment' => '_Comment',
            'Size' => '_Size',
        );
        $agentData = array(
            'NAME' => '_Name',
            'VERSION' => '_Version',
            'COMMENTS' => '_Comment',
            'PUBLISHER' => null,
            'FOLDER' => null,
            'SOURCE' => null,
            'GUID' => null,
            'LANGUAGE' => null,
            'INSTALLDATE' => null,
            'BITSWIDTH' => null,
            'FILESIZE' => '_Size',
        );
        $this->assertEquals($agentData, $hydrator->extract($software));
    }

    public function hydrateNameProvider()
    {
        return array(
            array('NAME', 'Name'),
            array('VERSION', 'Version'),
            array('COMMENTS', 'Comment'),
            array('PUBLISHER', 'Publisher'),
            array('FOLDER', 'InstallLocation'),
            array('SOURCE', 'IsHotfix'),
            array('GUID', 'Guid'),
            array('LANGUAGE', 'Language'),
            array('INSTALLDATE', 'InstallationDate'),
            array('BITSWIDTH', 'Architecture'),
            array('FILESIZE', 'Size'),
        );
    }

    /**
     * @dataProvider hydrateNameProvider
     */
    public function testHydrateName($extracted, $hydrated)
    {
        $hydrator = new \Protocol\Hydrator\Software;
        $this->assertEquals($hydrated, $hydrator->hydrateName($extracted));
    }

    public function testHydrateNameInvalid()
    {
        $this->expectException('DomainException', 'Cannot hydrate name: invalid');
        $hydrator = new \Protocol\Hydrator\Software;
        $hydrator->hydrateName('invalid');
    }

    public function extractNameProvider()
    {
        return array(
            array('Name', 'NAME'),
            array('Version', 'VERSION'),
            array('Comment', 'COMMENTS'),
            array('Publisher', 'PUBLISHER'),
            array('InstallLocation', 'FOLDER'),
            array('IsHotfix', 'SOURCE'),
            array('Guid', 'GUID'),
            array('Language', 'LANGUAGE'),
            array('InstallationDate', 'INSTALLDATE'),
            array('Architecture', 'BITSWIDTH'),
            array('Size', 'FILESIZE'),
        );
    }

    /**
     * @dataProvider extractNameProvider
     */
    public function testExtractName($hydrated, $extracted)
    {
        $hydrator = new \Protocol\Hydrator\Software;
        $this->assertEquals($extracted, $hydrator->extractName($hydrated));
    }

    public function testExtractNameInvalid()
    {
        $this->expectException('DomainException', 'Cannot extract name: Invalid');
        $hydrator = new \Protocol\Hydrator\Software;
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
            array('InstallationDate', '2014/12/31', new \DateTime('2014-12-31')),
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
        $hydrator = new \Protocol\Hydrator\Software;
        $this->assertEquals($hydrated, $hydrator->hydrateValue($name, $extracted));
    }

    public function extractValueProvider()
    {
        return array(
            array('SOURCE', true, '0'),
            array('SOURCE', false, '1'),
            array('INSTALLDATE', new \DateTime('2014-12-31'), '2014/12/31'),
            array('INSTALLDATE', '', null),
            array('INSTALLDATE', null, null),
            array('other', 'value', 'value'),
        );
    }

    /**
     * @dataProvider extractValueProvider
     */
    public function testExtractValue($name, $hydrated, $extracted)
    {
        $hydrator = new \Protocol\Hydrator\Software;
        $this->assertEquals($extracted, $hydrator->extractValue($name, $hydrated));
    }
}
