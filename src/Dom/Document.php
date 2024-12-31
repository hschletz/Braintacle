<?php

namespace Braintacle\Dom;

use DOMDocument;
use LogicException;
use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * DOMDocument extension with convenience functions.
 */
class Document extends DOMDocument
{
    public function __construct(string $version = '1.0', string $encoding = 'utf-8')
    {
        parent::__construct($version, $encoding);
    }

    /**
     * Create, append and return root node.
     */
    public function createRoot(string $name): Element
    {
        $element = $this->appendChild(new Element($name));
        assert($element instanceof Element);

        return $element;
    }

    /**
     * Retrieve full path to the RELAX NG schema file defining this document type.
     *
     * This is not implemented (throws an exception). Subclasses can override
     * this method to support validation.
     *
     * @throws LogicException if not implemented
     */
    public function getSchemaFilename(): string
    {
        throw new LogicException(get_class($this) . ' has no schema defined');
    }

    /**
     * Validate document, return status.
     *
     * The document is validated against the RELAX NG schema defined by
     * getSchemaFilename() which must be implemented by a subclass. Details are
     * available from the generated warnings.
     */
    public function isValid(): bool
    {
        return $this->relaxNGValidate($this->getSchemaFilename());
    }

    /**
     * Validate document, throw exception on error.
     *
     * The document gets validated against the RELAX NG schema defined by
     * getSchemaFilename() which must be implemented by a subclass. A
     * RuntimeException is thrown on error. Details are shown in the exception
     * message.
     *
     * **Warning:** The libXML error buffer gets reset before validation. It
     * will only contain errors relevant to the current validation afterwards.
     *
     * @throws RuntimeException if document is not valid
     */
    public function forceValid(): void
    {
        libxml_clear_errors();
        $useErrors = libxml_use_internal_errors(true);
        $isValid = $this->isValid();
        if (!$isValid) {
            $message = 'Validation of XML document failed.';
            foreach (libxml_get_errors() as $error) {
                $message .= sprintf(' line %d: %s', $error->line, $error->message);
            }
        }
        libxml_use_internal_errors($useErrors);
        if (!$isValid) {
            throw new RuntimeException($message);
        }
    }

    /**
     * Write XML content to file.
     *
     * This is a replacement for save() with improved error handling. An
     * exception is thrown on error, and no file remains on disk.
     *
     * @throws RuntimeException if a write error occurs
     */
    public function write(string $filename): void
    {
        // Don't use parent::save(). It won't report a disk full condition, and
        // a truncated file would remain on disk.
        $xml = $this->saveXml();
        $fileSystem = new Filesystem();
        $fileSystem->dumpFile($filename, $xml);
    }
}
