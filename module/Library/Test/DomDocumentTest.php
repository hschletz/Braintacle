<?php

/**
 * Tests for DomDocument
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

namespace Library\Test;

use Library\DomDocument;
use org\bovigo\vfs\vfsStream;

/**
 * Tests for DomDocument
 */
class DomDocumentTest extends \PHPUnit\Framework\TestCase
{
    public function testGetSchemaFilenameThrowsException()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Library\DomDocument has no schema defined');
        $document = new DomDocument();
        $document->getSchemaFilename();
    }

    public function testIsValidThrowsException()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Library\DomDocument has no schema defined');
        $document = new DomDocument();
        $document->isValid();
    }

    public function testIsValidWithDefinedSchema()
    {
        $document = $this->createPartialMock(DomDocument::class, ['getSchemaFilename', 'relaxNGValidate']);
        $document->method('getSchemaFilename')
                 ->willReturn('schema_file');
        $document->method('relaxNGValidate')
                 ->with('schema_file')
                 ->willReturn(true);
        $this->assertTrue($document->isValid());
    }

    public function testForceValidValid()
    {
        $document = $this->createPartialMock(DomDocument::class, ['isValid']);
        $document->expects($this->once())
                 ->method('isValid')
                 ->willReturn(true);
        $document->forceValid();
    }

    public function testForceValidNotValid()
    {
        $this->expectException('RuntimeException');
        $this->expectExceptionMessage('Validation of XML document failed. line 1: Expecting element TEST, got test');

        $document = $this->createPartialMock(DomDocument::class, ['getSchemaFilename']);
        $document->method('getSchemaFilename')->willReturn(__DIR__ . '/../data/Test/DomDocument/test.rng');

        $document->loadXML('<?xml version="1.0" ?><test />');
        @$document->forceValid();
    }

    public function testWrite()
    {
        $root = vfsStream::setup('root');
        $filename = $root->url() . '/test.xml';
        $document = new DomDocument();
        $node = $document->createElement('test');
        $document->appendChild($node);
        $document->write($filename);
        $expectedContent = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<test/>\n";
        $this->assertEquals(
            $expectedContent,
            file_get_contents($filename)
        );
    }
}
