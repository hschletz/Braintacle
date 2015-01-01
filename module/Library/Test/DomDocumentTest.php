<?php
/**
 * Tests for DomDocument
 *
 * Copyright (C) 2011-2015 Holger Schletz <holger.schletz@web.de>
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
        $length = $document->save($filename);
        $expectedContent = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<test/>\n";
        $this->assertEquals(
            $expectedContent,
            file_get_contents($filename)
        );
        $this->assertEquals(strlen($expectedContent), $length);
    }

    public function testSaveExplicitOptions()
    {
        $root = vfsStream::setup('root');
        $filename = $root->url() . '/test.xml';
        $document = new DomDocument;
        $node = $document->createElement('test');
        $document->appendChild($node);
        $length = $document->save($filename, LIBXML_NOEMPTYTAG);
        $expectedContent = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<test></test>\n";
        $this->assertEquals(
            $expectedContent,
            file_get_contents($filename)
        );
        $this->assertEquals(strlen($expectedContent), $length);
    }

    public function testSaveRemovesFileOnError()
    {
        $root = vfsStream::setup('root');
        $filename = $root->url() . '/test.xml';
        $document = new DomDocument;
        vfsStream::setQuota(1); // File is opened, written but truncated
        try {
            $document->save($filename);
            $this->fail('Expected exception has not been thrown');
        } catch (\RuntimeException $e) {
            $this->assertEquals("Error writing to file $filename", $e->getMessage());
            $this->assertFileNotExists($filename);
        }
    }

    public function testLoadSuccessWithXmlDeclaration()
    {
        $root = vfsStream::setup('root');
        $content = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\n<test>\xC4</test>\n";
        $filename = vfsStream::newFile('test.xml')->withContent($content)->at($root)->url();
        $document = new DomDocument;
        $this->assertTrue($document->load($filename));
        $this->assertEquals('ISO-8859-1', $document->xmlEncoding);
        $this->assertEquals('ISO-8859-1', $document->encoding);
        $node = $document->firstChild;
        $this->assertEquals('test', $node->tagName);
        $this->assertEquals("\xC3\x84", $node->nodeValue);
    }

    public function testLoadSuccessWithoutXmlDeclaration()
    {
        $root = vfsStream::setup('root');
        $content = "<test>\xC3\x84</test>\n";
        $filename = vfsStream::newFile('test.xml')->withContent($content)->at($root)->url();
        $document = new DomDocument;
        $this->assertTrue($document->load($filename));
        $node = $document->firstChild;
        $this->assertEquals('test', $node->tagName);
        $this->assertEquals("\xC3\x84", $node->nodeValue);
    }

    public function testLoadInvalidContent()
    {
        $root = vfsStream::setup('root');
        $content = '';
        $filename = vfsStream::newFile('test.xml')->withContent($content)->at($root)->url();
        $this->setExpectedException('RuntimeException', "$filename is unreadable or has invalid content");
        $document = new DomDocument;
        $document->load($filename);
    }

    public function testLoadFileUnreadable()
    {
        $root = vfsStream::setup('root');
        $content = "test";
        $filename = $root->url() . '/test.xml';
        $this->setExpectedException('RuntimeException', "$filename is unreadable or has invalid content");
        $document = new DomDocument;
        $document->load($filename);
    }
}
