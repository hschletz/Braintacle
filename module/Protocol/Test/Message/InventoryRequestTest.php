<?php
/**
 * Tests for InventoryRequest message
 *
 * Copyright (C) 2011-2020 Holger Schletz <holger.schletz@web.de>
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

namespace Protocol\Test\Message;

use Model\Client\Client;
use Protocol\Message\InventoryRequest;
use Protocol\Message\InventoryRequest\Content;
use TheSeer\fDOM\fDOMElement;

class InventoryRequestTest extends \PHPUnit\Framework\TestCase
{
    public function testGetSchemaFilename()
    {
        $document = new InventoryRequest($this->createStub(Content::class));
        $this->assertEquals(
            realpath(__DIR__ . '/../../data/RelaxNG/InventoryRequest.rng'),
            $document->getSchemaFilename()
        );
    }
    public function testLoadClient()
    {
        $client = $this->createMock(Client::class);
        $client->method('offsetGet')->with('IdString')->willReturn('id_string');

        $content = $this->createMock(Content::class);
        $content->expects($this->at(0))->method('setClient')->with($client);
        $content->expects($this->at(1))->method('appendSections');

        $request = $this->createMock(fDOMElement::class);
        $request->expects($this->exactly(2))->method('appendElement')->withConsecutive(
            ['DEVICEID', 'id_string', true],
            ['QUERY', 'INVENTORY']
        );
        $request->method('appendChild')->with($content);

        $document = $this->getMockBuilder(InventoryRequest::class)
                         ->setConstructorArgs([$content])
                         ->setMethodsExcept(['loadClient'])
                         ->getMock();
        $document->method('appendElement')->with('REQUEST')->willReturn($request);

        $document->loadClient($client);
    }

    public function testGetFilename()
    {
        $document = new InventoryRequest($this->createStub(Content::class));
        $document->appendChild($document->createElement('DEVICEID', 'Name-2015-06-04-18-22-06'));
        $this->assertEquals('Name-2015-06-04-18-22-06.xml', $document->getFilename());
    }

    public function testGetFilenameInvalidName()
    {
        $this->expectException(
            'UnexpectedValueException',
            '!Name2015-06-04-18-22-06 is not a valid filename part'
        );
        $document = new InventoryRequest($this->createStub(Content::class));
        $document->appendChild($document->createElement('DEVICEID', '!Name2015-06-04-18-22-06'));
        $document->getFilename();
    }

    public function testGetFilenameElementNotSet()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('DEVICEID element has not been set');
        $document = new InventoryRequest($this->createStub(Content::class));
        $document->getFilename();
    }
}
