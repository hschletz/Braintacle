<?php
/**
 * Tests for DomDocument
 *
 * Copyright (C) 2011-2014 Holger Schletz <holger.schletz@web.de>
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
class DomDocumentTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructorDefaults()
    {
        $document = new DomDocument;
        $this->assertEquals('1.0', $document->xmlVersion);
        $this->assertEquals('UTF-8', $document->xmlEncoding);
        $this->assertEquals('UTF-8', $document->encoding);
        $this->assertTrue($document->formatOutput);
    }

    public function testGetSchemaFilenameThrowsException()
    {
        $this->setExpectedException('LogicException', 'Library\DomDocument has no schema defined');
        $document = new DomDocument;
        $document->getSchemaFilename();
    }

    public function testIsValidThrowsException()
    {
        $this->setExpectedException('LogicException', 'Library\DomDocument has no schema defined');
        $document = new DomDocument;
        $document->isValid();
    }

    public function testIsValidWithDefinedSchema()
    {
        $document = $this->getMockBuilder('Library\DomDocument')
                         ->setMethods(array('getSchemaFilename', 'relaxNGValidate'))
                         ->getMock();
        $document->method('getSchemaFilename')
                 ->willReturn('schema_file');
        $document->method('relaxNGValidate')
                 ->with('schema_file')
                 ->willReturn('validation_result');
        $this->assertEquals('validation_result', $document->isValid());
    }

    public function testForceValidValid()
    {
        $document = $this->getMockBuilder('Library\DomDocument')->setMethods(array('isValid'))->getMock();
        $document->expects($this->once())
                 ->method('isValid')
                 ->willReturn(true);
        $document->forceValid();
    }

    public function testForceValidNotValid()
    {
        $this->setExpectedException('RuntimeException', 'Validation of XML document failed');
        $document = $this->getMockBuilder('Library\DomDocument')->setMethods(array('isValid'))->getMock();
        $document->expects($this->once())
                 ->method('isValid')
                 ->willReturn(false);
        $document->forceValid();
    }

    public function testCreateElementWithContentScalar()
    {
        $document = new DomDocument;
        $element = $document->createElementWithContent('name', '<content&>'); // Test escaping
        $this->assertEquals('name', $element->tagName);
        $this->assertEquals('name', $element->nodeName);
        $this->assertEquals('<content&>', $element->nodeValue);
    }

    public function testCreateElementWithContentNull()
    {
        $document = new DomDocument;
        $element = $document->createElementWithContent('name', null);
        $this->assertEquals('name', $element->tagName);
        $this->assertEquals('name', $element->nodeName);
        $this->assertEquals('', $element->nodeValue);
    }

    public function testCreateElementWithContentArray()
    {
        $this->setExpectedException('InvalidArgumentException', 'Unsupported content type');
        $document = new DomDocument;
        $element = $document->createElementWithContent('name', array());
    }

    public function testCreateElementWithContentObject()
    {
        $this->setExpectedException('InvalidArgumentException', 'Unsupported content type');
        $document = new DomDocument;
        $element = $document->createElementWithContent('name', new \stdClass);
    }

    public function testSaveDefaultOptions()
    {
        $root = vfsStream::setup('root');
        $filename = $root->url() . '/test.xml';
        $document = new DomDocument;
        $node = $document->createElement('test');
        $document->appendChild($node);
        $document->save($filename);
        $this->assertEquals(
            "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<test/>\n",
            file_get_contents($filename)
        );
    }

    public function testSaveExplicitOptions()
    {
        $root = vfsStream::setup('root');
        $filename = $root->url() . '/test.xml';
        $document = new DomDocument;
        $node = $document->createElement('test');
        $document->appendChild($node);
        $document->save($filename, LIBXML_NOEMPTYTAG);
        $this->assertEquals(
            "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<test></test>\n",
            file_get_contents($filename)
        );
    }
}
