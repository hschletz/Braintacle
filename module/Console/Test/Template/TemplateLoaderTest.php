<?php

namespace Console\Test\Template;

use Console\Template\TemplateLoader;
use Latte\RuntimeException;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Path;

class TemplateLoaderTest extends TestCase
{
    public function getContentProvider()
    {
        return [
            [-10], // past
            [0], // present
            [+10], // future - would cause default implementation to touch template
        ];
    }

    /** @dataProvider getContentProvider */
    public function testGetContent(int $mtimeOffset)
    {
        $mtime = time() + $mtimeOffset;

        $root = vfsStream::setup('templates');
        $template = vfsStream::newFile('template.latte')->at($root)->setContent('content');
        $template->lastModified($mtime);

        $loader = new TemplateLoader($root->url());
        $this->assertEquals('content', $loader->getContent('template.latte'));

        $this->assertEquals($mtime, $template->filemtime(), 'Template mtime got touched');
    }

    public function testGetContentWithInvalidPath()
    {
        $root = vfsStream::setup('templates');
        $baseDir = vfsStream::newDirectory('subdir')->at($root)->url();
        $template = vfsStream::newFile('template.latte')->at($root)->url();
        $fileName = Path::makeRelative($template, $baseDir); // '../template.latte'

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Template '$template' is not within the allowed path '$baseDir/'.");

        $loader = new TemplateLoader($baseDir);
        $loader->getContent($fileName);
    }

    public function testGetContentWithMissingFile()
    {
        $baseDir = vfsStream::setup('templates')->url();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Missing template file '$baseDir/template.latte'.");

        $loader = new TemplateLoader($baseDir);
        $loader->getContent('template.latte');
    }
}
