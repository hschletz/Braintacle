<?php

namespace Braintacle\Test\Package\Build;

use Braintacle\Package\Build\SourceFile;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SourceFile::class)]
final class SourceFileTest extends TestCase
{
    public function testConstructor()
    {
        $sourceFile = new SourceFile('_name', '_path');
        $this->assertEquals('_name', $sourceFile->name);
        $this->assertEquals('_path', $sourceFile->path);
    }
}
