<?php

namespace Braintacle\Test\Dom;

use Braintacle\Dom\Document;
use DOMElement;
use LogicException;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class DocumentTest extends TestCase
{
    public function testCreateRoot()
    {
        $document = new Document();
        $root = $document->createRoot('root');
        $root->appendChild(new DOMElement('leaf'));
        $this->assertEquals('root', $root->tagName);
        $this->assertXmlStringEqualsXmlString('<root><leaf /></root>', $document->saveXML());
    }

    public function testGetSchemaFilenameThrowsException()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(Document::class . ' has no schema defined');
        $document = new Document();
        $document->getSchemaFilename();
    }

    public function testIsValid()
    {
        $document = $this->createPartialMock(Document::class, ['getSchemaFilename', 'relaxNGValidate']);
        $document->method('getSchemaFilename')->willReturn('schema_file');
        $document->method('relaxNGValidate')->with('schema_file')->willReturn(true);
        $this->assertTrue($document->isValid());
    }

    public function testForceValidValid()
    {
        $document = $this->createPartialMock(Document::class, ['isValid']);
        $document->expects($this->once())->method('isValid')->willReturn(true);
        $document->forceValid();
    }

    public function testForceValidNotValid()
    {
        $root = vfsStream::setup('root');
        $schemaFile = vfsStream::newFile('schema.rng')->at($root)->withContent('<?xml version="1.0" encoding="UTF-8" ?>
            <grammar xmlns="http://relaxng.org/ns/structure/1.0">
                <start>
                    <element name="TEST">
                        <text />
                    </element>
                </start>
            </grammar>
        ');

        $document = $this->createPartialMock(Document::class, ['getSchemaFilename']);
        $document->method('getSchemaFilename')->willReturn($schemaFile->url());
        $document->loadXML('<?xml version="1.0" ?><test />');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Validation of XML document failed. line 1: Expecting element TEST, got test');

        @$document->forceValid();
    }

    public function testWrite()
    {
        $root = vfsStream::setup('root');
        $filename = $root->url() . '/test.xml';

        $document = new Document();
        $node = $document->createElement('test');
        $document->appendChild($node);
        $document->write($filename);

        $this->assertXmlStringEqualsXmlFile($filename, '<?xml version="1.0" encoding="utf-8"?><test/>');
    }
}
