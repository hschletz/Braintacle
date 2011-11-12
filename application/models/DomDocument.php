<?php
/**
 * Interface to XML documents
 *
 * $Id$
 *
 * Copyright (C) 2011 Holger Schletz <holger.schletz@web.de>
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
 *
 * @package Models
 * @filesource
 */
/**
 * Interface to XML documents
 *
 * This class extends the DOMDocument class with application specific
 * functionality. It is a base class for document type specific subclasses.
 * @package Models
 */
abstract class Model_DomDocument extends DOMDocument
{

    /**
     * Constructor
     *
     * This constructor provides reasonable defaults, so it can be typically
     * invoked without arguments and subsequent initilaization.
     * @param string $version Default: 1.0
     * @param string $encoding Default: UTF-8
     */
    function __construct($version='1.0', $encoding='UTF-8')
    {
        parent::__construct($version, $encoding);
        $this->formatOutput = true;
    }

    /**
     * Validate document, return status
     *
     * The document gets validated against the matching RELAX NG schema file in
     * the xml/ directory. Details are available from the generated warnings.
     * @return bool Validation result
     */
    public function isValid()
    {
        // Determine schema filename from subclass name
        $schema = get_class($this);
        $schema = substr(
            $schema,
            strrpos($schema, '_') + 1
        );
        return $this->relaxNGValidate(APPLICATION_PATH . "/../xml/$schema.rng");
    }

    /**
     * Validate document, throw exception on error
     *
     * The document gets validated against the matching RELAX NG schema file in
     * the xml/ directory. A RuntimeException is thrown on error. Details are
     * available from the generated warnings.
     */
    public function forceValid()
    {
        if (!$this->isValid()) {
            throw new RuntimeException('Validation of XML document failed');
        }
    }

    /**
     * Create element with text content
     *
     * This is similat to the 2-argument-variant of createElement(), but the
     * text gets properly escaped.
     * @param string $name Element name
     * @param mixed $content Element content
     * @return DOMElement
     */
    public function createElementWithContent($name, $content)
    {
        if (is_scalar($content) or is_null($content)) {
            $content = $this->createTextNode($content);
        } else {
            var_dump($content);
            throw new InvalidArgumentException('Unsupported content type');
        }
        $element = $this->createElement($name);
        $element->appendChild($content);
        return $element;
    }
}
