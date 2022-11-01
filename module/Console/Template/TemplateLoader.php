<?php

namespace Console\Template;

use Latte\Loaders\FileLoader;
use Latte\RuntimeException;
use Library\FileObject;
use Symfony\Component\Filesystem\Path;

/**
 * Template loader.
 *
 * Extends standard FileLoader, but never touches template source files.
 */
class TemplateLoader extends FileLoader
{
    public function __construct(string $baseDir)
    {
        parent::__construct($baseDir);
    }

    /**
     * @psalm-suppress ParamNameMismatch The mismatch is actually between FileLoader and Loader.
     */
    public function getContent($fileName): string
    {
        $file = Path::join($this->baseDir, $fileName);
        if (!path::isBasePath($this->baseDir, $file)) {
            throw new RuntimeException("Template '$file' is not within the allowed path '{$this->baseDir}'.");
        }
        if (!is_file($file)) {
            throw new RuntimeException("Missing template file '$file'.");
        }

        return FileObject::fileGetContents($file);
    }
}
