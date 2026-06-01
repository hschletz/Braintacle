<?php

namespace Braintacle\Package\Build;

/**
 * Package source file abstraction.
 *
 * Source files may have been uploaded through the webinterface, or originate
 * from the local filesystem. This class provides an abstract definition. The
 * SourceFileFactory generates an instance from a given source.
 */
final class SourceFile
{
    /**
     * @param string $name Name under which the file will be known to the client.
     * @param string $path Full path to the source file.
     */
    public function __construct(
        public readonly string $name,
        public readonly string $path,
    ) {}
}
