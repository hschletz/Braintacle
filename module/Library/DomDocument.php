<?php
/**
 * DOM document
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

namespace Library;

/**
 * DOM document
 *
 * This class extends \DOMDocument with some convenience functions.
 */
class DomDocument extends \DOMDocument
{
    /**
     * Constructor
     *
     * This constructor provides reasonable defaults, so that it can typically
     * be invoked without arguments and subsequent initialization. String output
     * is formatted by default (formatOutput property).
     *
     * @param string $version Default: 1.0
     * @param string $encoding Default: UTF-8
     */
    function __construct($version='1.0', $encoding='UTF-8')
    {
        parent::__construct($version, $encoding);
        $this->formatOutput = true;
    }

    /**
     * Retrieve full path to the RELAX NG schema file defining this document type
     *
     * This is not implemented (throws an exception). Subclasses can override
     * this method to support validation.
     *
     * @return string
     * @throws \LogicException if not implemented
     */
    public function getSchemaFilename()
    {
        throw new \LogicException(get_class($this) . ' has no schema defined');
    }

    /**
     * Validate document, return status
     *
     * The document is validated against the RELAX NG schema defined by
     * getSchemaFilename() which must be implemented by a subclass. Details are
     * available from the generated warnings.
     *
     * @return bool Validation result
     */
    public function isValid()
    {
        return $this->relaxNGValidate($this->getSchemaFilename());
    }

    /**
     * Validate document, throw exception on error
     *
     * The document gets validated against the RELAX NG schema defined by
     * getSchemaFilename() which must be implemented by a subclass.
     * A \RuntimeException is thrown on error. Details are available from the
     * generated warnings.
     *
     * @throws \RuntimeException if document is not valid
     */
    public function forceValid()
    {
        if (!$this->isValid()) {
            throw new \RuntimeException('Validation of XML document failed');
        }
    }

    /**
     * Create element with text content
     *
     * This is similar to the 2-argument variant of createElement(), but the
     * text gets properly escaped.
     *
     * @param string $name Element name
     * @param mixed $content Element content
     * @return \DOMElement
     * @throws \InvalidArgumentException if $content has non-scalar type
     */
    public function createElementWithContent($name, $content)
    {
        if (is_scalar($content) or is_null($content)) {
            $content = $this->createTextNode($content);
        } else {
            throw new \InvalidArgumentException('Unsupported content type');
        }
        $element = $this->createElement($name);
        $element->appendChild($content);
        return $element;
    }

    /**
     * Write XML content to file
     *
     * This is a reimplementation of \DomDocument::save() with improved error
     * handling. An exception is thrown on error, and no file remains on disk.
     *
     * @param string $filename
     * @param integer $options
     * @return integer number of bytes written
     * @throws \RuntimeException if a write error occurs
     */
    public function save($filename, $options=0)
    {
        $xml = $this->saveXml(null, $options);
        try {
            \Library\FileObject::filePutContents($filename, $xml);
        } catch (\Exception $e) {
            if (is_file($filename)) {
                unlink($filename);
            }
            throw $e;
        }
        return strlen($xml);
    }

    /**
     * Load XML content from file
     *
     * This is an extension of \DomDocument::load() with improved error
     * handling. An exception is thrown on error.
     *
     * @param string $filename
     * @param integer $options
     * @return bool always TRUE for compatibility with original implementation
     * @throws \RuntimeException if file is unreadable or has unparseable content
     */
    public function load($filename, $options=0)
    {
        if (@parent::load($filename, $options)) {
            return true;
        } else {
            throw new \RuntimeException($filename . ' is unreadable or has invalid content');
        }
    }
}
